<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FunnelEvent extends Model
{
    public const UPDATED_AT = null;

    /** Eventos aceitos pelo /api/track (qualquer outro é rejeitado). */
    public const EVENTS = [
        'presell_view',
        'presell_scroll_50',
        'presell_cta_click',
        'quiz_start',
        'quiz_location_resolved',
        'quiz_step_view',
        'quiz_profile_selected',
        'quiz_lives_defined',
        'quiz_plans_loaded',
        'quiz_plan_selected',
        'quiz_plan_deselected',
        'quiz_proposal_requested',
        'quiz_whatsapp_submit',
        'quiz_error',
        'generate_lead',
    ];

    /** Nome de cada passo do quiz (step 6 = tela de sucesso). */
    public const STEP_NAMES = [
        1 => 'Nome',
        2 => 'Perfil',
        3 => 'Vidas',
        4 => 'Planos',
        5 => 'WhatsApp',
        6 => 'Sucesso',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'props' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
