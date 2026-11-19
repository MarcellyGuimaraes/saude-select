<?php

namespace App\Http\Controllers;

use App\Services\FunnelReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FunnelDashboardController extends Controller
{
    private const TZ = 'America/Sao_Paulo';

    public function index(Request $request, FunnelReport $report): View
    {
        $this->authorizeAccess($request);

        $period = in_array($request->query('period'), ['today', '7d', '30d'], true)
            ? $request->query('period')
            : '7d';

        $now = now(self::TZ);
        $from = match ($period) {
            'today' => $now->startOfDay(),
            '30d' => $now->subDays(29)->startOfDay(),
            default => $now->subDays(6)->startOfDay(),
        };
        $from = $from->utc();
        $to = $now->utc();

        $filters = [
            'source' => $request->query('source') ?: null,
            'campaign' => $request->query('campaign') ?: null,
        ];

        $funnel = $report->funnel($from, $to, $filters);

        return view('admin.funil', [
            'period' => $period,
            'filters' => $filters,
            'options' => $report->filterOptions(),
            'funnel' => $funnel,
            'bySource' => $report->bySource($from, $to, $filters),
            'breakdown' => $report->leadBreakdown($from, $to, $filters),
            'perDay' => $report->leadsPerDay($from, $to, $filters),
            'errors' => $report->topErrors($from, $to, $filters),
            'cards' => [
                'presell' => $funnel[0]['count'],
                'quiz' => $funnel[2]['count'],
                'leads' => $funnel[8]['count'],
                'rate' => $funnel[2]['count'] > 0 ? round($funnel[8]['count'] / $funnel[2]['count'] * 100, 1) : null,
            ],
        ]);
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
