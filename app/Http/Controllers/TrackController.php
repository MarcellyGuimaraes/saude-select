<?php

namespace App\Http\Controllers;

use App\Models\FunnelEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Recebe os eventos de navegação do funil. Nunca deve quebrar a experiência
 * do visitante: qualquer falha interna é só logada e responde 204.
 */
class TrackController extends Controller
{
    private const UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    /** Chaves que nunca são gravadas, mesmo se o front enviar. */
    private const BLOCKED_PROPS = ['phone', 'telefone', 'whatsapp', 'nome', 'name', 'email'];

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event' => ['required', 'string', Rule::in(FunnelEvent::EVENTS)],
            'session_id' => ['required', 'string', 'max:64'],
            'page' => ['nullable', 'string', 'max:120'],
            'step' => ['nullable', 'integer', 'min:1', 'max:6'],
            'utm' => ['nullable', 'array'],
            'props' => ['nullable', 'array'],
        ]);

        try {
            $utm = is_array($data['utm'] ?? null) ? $data['utm'] : [];
            $props = $this->cleanProps($data['props'] ?? []);

            FunnelEvent::create([
                'session_id' => $data['session_id'],
                'event' => $data['event'],
                'step' => $data['step'] ?? null,
                'page' => $data['page'] ?? null,
                'profile' => $this->str($props['profile'] ?? null, 20),
                'state_name' => $this->str($props['state_name'] ?? null, 60),
                'utm_source' => $this->str($utm['utm_source'] ?? null, 150),
                'utm_medium' => $this->str($utm['utm_medium'] ?? null, 150),
                'utm_campaign' => $this->str($utm['utm_campaign'] ?? null, 150),
                'utm_content' => $this->str($utm['utm_content'] ?? null, 150),
                'utm_term' => $this->str($utm['utm_term'] ?? null, 150),
                'props' => $props ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Track: falha ao gravar evento', ['event' => $data['event'], 'error' => $e->getMessage()]);
        }

        return response()->json(null, 204);
    }

    /** Mantém só valores escalares curtos, no máximo 20 chaves, sem dados pessoais. */
    private function cleanProps(array $props): array
    {
        $clean = [];

        foreach ($props as $key => $value) {
            if (count($clean) >= 20) {
                break;
            }
            if (! is_string($key) || in_array(strtolower($key), self::BLOCKED_PROPS, true)) {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $clean[$key] = is_string($value) ? mb_substr($value, 0, 150) : $value;
            }
        }

        return $clean;
    }

    private function str(mixed $value, int $max): ?string
    {
        return is_scalar($value) && $value !== '' ? mb_substr((string) $value, 0, $max) : null;
    }
}
