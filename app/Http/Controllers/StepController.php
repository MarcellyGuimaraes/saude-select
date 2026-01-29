<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchHospitalsRequest;
use App\Http\Requests\SearchPlansRequest;
use App\Services\SimuladorOnlineService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class StepController extends Controller
{
    public function __construct(
        protected SimuladorOnlineService $simuladorService
    ) {}

    public function show(int $step): View
    {
        if ($step < 1 || $step > 5) {
            abort(404);
        }

        return view("steps.step-{$step}");
    }

    public function final(): View
    {
        return view('steps.step-final');
    }

    public function buscarHospitais(SearchHospitalsRequest $request): JsonResponse
    {
        try {
            $query = $request->validated('q');
            $result = $this->simuladorService->searchHospitalsForAutocomplete($query);

            $payload = [
                'success' => true,
                'hospitais' => $result['hospitais'],
            ];

            if (config('app.debug')) {
                $payload['debug'] = [
                    'raw_response' => $result['raw'],
                    'query' => $result['query'],
                    'regiao' => $result['region'],
                ];
            }

            return response()->json($payload);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function buscarPlanos(SearchPlansRequest $request): JsonResponse
    {
        try {
            $planos = $this->simuladorService->searchPlans($request->validated());

            return response()->json([
                'success' => true,
                'planos' => $planos,
                'total' => count($planos),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
}
