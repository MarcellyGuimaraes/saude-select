<?php

namespace App\Http\Controllers;

use App\Services\FunnelReport;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FunnelDashboardController extends Controller
{
    private const TZ = 'America/Sao_Paulo';

    public function index(Request $request, FunnelReport $report): View
    {
        $this->authorizeAccess($request);

        [$period, $from, $to] = $this->resolvePeriod($request);

        $funnel = $report->funnel($from->utc(), $to->utc());

        // Maior perda entre etapas consecutivas (só etapas comparáveis e com perda real).
        $worst = null;
        foreach ($funnel as $i => $row) {
            if ($row['lost_pct'] !== null && $row['dropped'] > 0
                && ($worst === null || $row['lost_pct'] > $funnel[$worst]['lost_pct'])) {
                $worst = $i;
            }
        }

        return view('admin.funil', [
            'period' => $period,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'rangeLabel' => $from->format('d/m/Y').' a '.$to->format('d/m/Y'),
            'funnel' => $funnel,
            'worst' => $worst,
            'hasData' => $funnel[0]['count'] > 0 || $funnel[2]['count'] > 0,
            'cards' => [
                'presell' => $funnel[0]['count'],
                'quiz' => $funnel[2]['count'],
                'leads' => $funnel[8]['count'],
                'rate' => $funnel[2]['count'] > 0 ? round($funnel[8]['count'] / $funnel[2]['count'] * 100, 1) : null,
            ],
        ]);
    }

    /**
     * Período: atalhos (hoje, 7d, 30d) ou intervalo personalizado (?period=custom&from=AAAA-MM-DD&to=AAAA-MM-DD).
     * Datas no fuso de São Paulo; intervalo inválido cai nos últimos 7 dias.
     *
     * @return array{0: string, 1: CarbonImmutable, 2: CarbonImmutable}
     */
    private function resolvePeriod(Request $request): array
    {
        $now = CarbonImmutable::now(self::TZ);
        $period = in_array($request->query('period'), ['today', '7d', '30d', 'custom'], true)
            ? $request->query('period')
            : '7d';

        if ($period === 'custom') {
            $from = $this->parseDate($request->query('from'));
            $to = $this->parseDate($request->query('to'));

            if ($from && $to && $from <= $to && $from->diffInDays($to) <= 366) {
                return ['custom', $from->startOfDay(), $to->endOfDay()->min($now)];
            }
            $period = '7d';
        }

        $from = match ($period) {
            'today' => $now->startOfDay(),
            '30d' => $now->subDays(29)->startOfDay(),
            default => $now->subDays(6)->startOfDay(),
        };

        return [$period, $from, $now];
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value, self::TZ);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Login por autenticacao basica: o navegador abre a janela de usuario e senha.
     * Sem credenciais corretas responde 401. Se nao houver usuario/hash configurado, fica desligado.
     */
    private function authorizeAccess(Request $request): void
    {
        $user = (string) config('services.funnel.user');
        $hash = (string) config('services.funnel.password_hash');

        $ok = $user !== '' && $hash !== ''
            && hash_equals($user, (string) $request->getUser())
            && password_verify((string) $request->getPassword(), $hash);

        abort_unless($ok, 401, 'Acesso restrito.', [
            'WWW-Authenticate' => 'Basic realm="BuscarPlanos Funil", charset="UTF-8"',
        ]);
    }
}
