<?php

namespace App\Services;

use App\Models\FunnelEvent;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Consultas do dashboard /admin/funil. Conta SESSÕES únicas por etapa
 * (um visitante que recarrega a página não infla o número).
 */
class FunnelReport
{
    /** Etapas do funil, na ordem. `step` só se aplica ao evento quiz_step_view. */
    public const STAGES = [
        ['label' => 'Presell: visualizou', 'event' => 'presell_view'],
        ['label' => 'Presell: clicou no botão', 'event' => 'presell_cta_click'],
        ['label' => 'Abriu o quiz', 'event' => 'quiz_start'],
        ['label' => 'Passo 1: Nome', 'event' => 'quiz_step_view', 'step' => 1],
        ['label' => 'Passo 2: Perfil', 'event' => 'quiz_step_view', 'step' => 2],
        ['label' => 'Passo 3: Vidas', 'event' => 'quiz_step_view', 'step' => 3],
        ['label' => 'Passo 4: Planos', 'event' => 'quiz_step_view', 'step' => 4],
        ['label' => 'Passo 5: WhatsApp', 'event' => 'quiz_step_view', 'step' => 5],
        ['label' => 'Lead gerado', 'event' => 'generate_lead'],
    ];

    /**
     * @return list<array{label: string, count: int, pct_prev: ?float, pct_top: ?float, dropped: int, lost_pct: ?float, direct: int}>
     *
     * - pct_prev: % da etapa anterior (null se não comparável, ex.: acessos diretos ao quiz)
     * - lost_pct: % que saiu em relação à etapa anterior
     * - direct: sessões a mais que a etapa anterior (entraram sem passar por ela, como acesso direto ao quiz)
     */
    public function funnel(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = [];
        $previous = null;
        $top = null;

        foreach (self::STAGES as $stage) {
            $count = FunnelEvent::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('event', $stage['event'])
                ->when(isset($stage['step']), fn (Builder $q) => $q->where('step', $stage['step']))
                ->distinct()
                ->count('session_id');

            $top ??= $count;
            $comparable = $previous !== null && $previous > 0 && $count <= $previous;

            $rows[] = [
                'label' => $stage['label'],
                'count' => $count,
                'pct_prev' => $comparable ? round($count / $previous * 100, 1) : null,
                'pct_top' => $top === 0 ? null : round($count / $top * 100, 1),
                'dropped' => $comparable ? $previous - $count : 0,
                'lost_pct' => $comparable ? round(($previous - $count) / $previous * 100, 1) : null,
                'direct' => $previous !== null && $count > $previous ? $count - $previous : 0,
            ];

            $previous = $count;
        }

        return $rows;
    }
}
