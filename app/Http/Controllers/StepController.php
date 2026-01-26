<?php

namespace App\Http\Controllers;

use App\Services\SimuladorOnlineService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StepController extends Controller
{
    public function __construct(
        protected \App\Services\SimuladorOnlineService $simuladorService
    ) {}

    public function show(int $step): View
    {
        // Valida se o step é válido (1 a 5)
        if ($step < 1 || $step > 5) {
            abort(404);
        }

        return view("steps.step-{$step}");
    }

    public function final(): View
    {
        return view('steps.step-final');
    }

    /**
     * Busca hospitais para autocomplete (Step 1)
     */
    public function buscarHospitais(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1',
        ]);

        try {
            $query = $request->input('q');

            // Sempre usa região 2 (Rio de Janeiro)
            $regiao = 2;

            $hospitais = $this->simuladorService->buscarHospitais($regiao, $query);

            // Formata a resposta para o frontend
            // A API pode retornar array de objetos ou array de strings
            $hospitaisFormatados = [];

            if (is_array($hospitais)) {
                foreach ($hospitais as $hospital) {
                    if (is_array($hospital)) {
                        // dd($hospital);
                        // Tenta diferentes formatos de resposta da API
                        $nome = $hospital['full_descricao'];

                        // Se ainda não encontrou, tenta pegar o primeiro valor não-numérico
                        if (empty($nome)) {
                            foreach ($hospital as $key => $value) {
                                if (is_string($value) && ! is_numeric($key)) {
                                    $nome = $value;
                                    break;
                                }
                            }
                        }

                        if ($nome) {
                            $hospitaisFormatados[] = [
                                'id' => $hospital['id'] ?? $hospital['codigo'] ?? null,
                                'nome' => $nome,
                            ];
                        }
                    } elseif (is_string($hospital)) {
                        // Formato string simples
                        $hospitaisFormatados[] = [
                            'id' => null,
                            'nome' => $hospital,
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'hospitais' => $hospitaisFormatados,
                'debug' => config('app.debug') ? [
                    'raw_response' => $hospitais,
                    'query' => $query,
                    'regiao' => $regiao,
                ] : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
