<?php

namespace App\Services;

use App\Models\FunnelEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Consultas do dashboard /admin/funil. Conta SESSÕES únicas por etapa
 * (um visitante que recarrega a página não infla o número).
 */
class FunnelReport
{
    /** Etapas do funil, na ordem. `step` só se aplica ao evento quiz_step_view. */
    public const STAGES = [
        ['label' => 'Presell: visualizou', 'event' => 'presell_view'],
        ['label' => 'Presell: clicou no CTA', 'event' => 'presell_cta_click'],
        ['label' => 'Abriu o quiz', 'event' => 'quiz_start'],
        ['label' => 'Passo 1: Nome', 'event' => 'quiz_step_view', 'step' => 1],
        ['label' => 'Passo 2: Perfil', 'event' => 'quiz_step_view', 'step' => 2],
        ['label' => 'Passo 3: Vidas', 'event' => 'quiz_step_view', 'step' => 3],
        ['label' => 'Passo 4: Planos', 'event' => 'quiz_step_view', 'step' => 4],
        ['label' => 'Passo 5: WhatsApp', 'event' => 'quiz_step_view', 'step' => 5],
        ['label' => 'Lead gerado', 'event' => 'generate_lead'],
    ];

    /**
     * @param  array{source?: ?string, campaign?: ?string}  $filters
     * @return list<array{label: string, count: int, pct_prev: ?float, pct_top: ?float, dropped: int}>
     */
    public function funnel(Carbon $from, Carbon $to, array $filters = []): array
    {
        $rows = [];
        $previous = null;
        $top = null;

        foreach (self::STAGES as $stage) {
            $count = $this->base($from, $to, $filters)
                ->where('event', $stage['event'])
                ->when(isset($stage['step']), fn (Builder $q) => $q->where('step', $stage['step']))
                ->distinct()
                ->count('session_id');

            $top ??= $count;

            $rows[] = [
                'label' => $stage['label'],
                'count' => $count,
                'pct_prev' => $previous === null || $previous === 0 ? null : round($count / $previous * 100, 1),
                'pct_top' => $top === 0 ? null : round($count / $top * 100, 1),
                'dropped' => $previous === null ? 0 : max(0, $previous - $count),
            ];

            $previous = $count;
        }

        return $rows;
    }

    /**
     * Quiz iniciado x leads por origem/campanha.
     *
     * @return list<array{source: string, campaign: string, started: int, leads: int, rate: ?float}>
     */
    public function bySource(Carbon $from, Carbon $to, array $filters = []): array
    {
        $count = function (string $event) use ($from, $to, $filters) {
            return $this->base($from, $to, $filters)
                ->where('event', $event)
                ->selectRaw('utm_source, utm_campaign, count(distinct session_id) as total')
                ->groupBy('utm_source', 'utm_campaign')
                ->get()
                ->mapWithKeys(fn ($r) => [($r->utm_source ?? '') . '|' . ($r->utm_campaign ?? '') => (int) $r->total]);
        };

        $started = $count('quiz_start');
        $leads = $count('generate_lead');

        return $started->keys()->merge($leads->keys())->unique()->map(function (string $key) use ($started, $leads) {
            [$source, $campaign] = explode('|', $key, 2);
            $s = $started[$key] ?? 0;
            $l = $leads[$key] ?? 0;

            return [
                'source' => $source !== '' ? $source : '(direto)',
                'campaign' => $campaign !== '' ? $campaign : '(sem campanha)',
                'started' => $s,
                'leads' => $l,
                'rate' => $s > 0 ? round($l / $s * 100, 1) : null,
            ];
        })->sortByDesc('started')->values()->all();
    }

    /**
     * Leads por estado e por perfil (CPF/PME/adesão).
     *
     * @return array{states: array<string, int>, profiles: array<string, int>}
     */
    public function leadBreakdown(Carbon $from, Carbon $to, array $filters = []): array
    {
        $group = fn (string $column) => $this->base($from, $to, $filters)
            ->where('event', 'generate_lead')
            ->whereNotNull($column)
            ->selectRaw("$column as k, count(distinct session_id) as total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->pluck('total', 'k')
            ->map(fn ($v) => (int) $v)
            ->all();

        return ['states' => $group('state_name'), 'profiles' => $group('profile')];
    }

    /**
     * Leads por dia (fuso de São Paulo).
     *
     * @return array<string, int> data (d/m) => leads
     */
    public function leadsPerDay(Carbon $from, Carbon $to, array $filters = []): array
    {
        $days = [];

        $this->base($from, $to, $filters)
            ->where('event', 'generate_lead')
            ->get(['session_id', 'created_at'])
            ->unique('session_id')
            ->each(function ($row) use (&$days) {
                $day = $row->created_at->copy()->setTimezone('America/Sao_Paulo')->format('d/m');
                $days[$day] = ($days[$day] ?? 0) + 1;
            });

        return $days;
    }

    /**
     * Erros mais frequentes (mensagem exibida ao visitante).
     *
     * @return array<string, int>
     */
    public function topErrors(Carbon $from, Carbon $to, array $filters = []): array
    {
        return $this->base($from, $to, $filters)
            ->where('event', 'quiz_error')
            ->latest('created_at')
            ->limit(500)
            ->get(['props'])
            ->map(fn ($r) => (string) ($r->props['message'] ?? 'sem mensagem'))
            ->countBy()
            ->sortDesc()
            ->take(8)
            ->all();
    }

    /** Valores distintos para os filtros do dashboard. */
    public function filterOptions(): array
    {
        $distinct = fn (string $column) => FunnelEvent::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->limit(100)
            ->pluck($column)
            ->all();

        return ['sources' => $distinct('utm_source'), 'campaigns' => $distinct('utm_campaign')];
    }

    private function base(Carbon $from, Carbon $to, array $filters): Builder
    {
        return FunnelEvent::query()
            ->whereBetween('created_at', [$from, $to])
            ->when(! empty($filters['source']), fn (Builder $q) => $q->where('utm_source', $filters['source']))
            ->when(! empty($filters['campaign']), fn (Builder $q) => $q->where('utm_campaign', $filters['campaign']));
    }
}
