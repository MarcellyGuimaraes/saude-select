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
        $configured = (string) config('services.funnel.dashboard_key');
        $given = (string) ($request->query('key') ?: $request->session()->get('funnel_key', ''));

        // Sem chave configurada o dashboard fica desligado; chave errada = 404 (não revela que existe).
        abort_unless($configured !== '' && hash_equals($configured, $given), 404);
        $request->session()->put('funnel_key', $configured);

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
}
