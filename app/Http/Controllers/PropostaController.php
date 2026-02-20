<?php

namespace App\Http\Controllers;

use App\Services\PdfService;
use App\Services\SimuladorOnlineService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropostaController extends Controller
{
    public function __construct(protected
        SimuladorOnlineService $simuladorService, protected
        PdfService $pdfService
        )
    {
    }

    public function gerar(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'planIds' => 'required|array',
            'lives' => 'required|array',
            'profile' => 'required|string',
            'profession_id' => 'nullable|string',
            'nome' => 'nullable|string|max:255',
        ]);

        try {
            $rawHtml = $this->simuladorService->getSimulationRawHtml(
                $data['planIds'],
                $data['lives'],
                $data['profile'],
                $data['nome'] ?? null,
                $data['profession_id'] ?? null
            );

            $baseUrl = config('services.simulador_online.base_url', 'https://app.simuladoronline.com');
            $systemHtml = SimuladorOnlineService::extractProposalContent($rawHtml, $baseUrl);
            $clientHtml = SimuladorOnlineService::extractClientProposalContent($rawHtml, $baseUrl);

            $plansWithoutInternacao = SimuladorOnlineService::identifyPlansWithoutInternacao($clientHtml);

            // PRÉ-GERAÇÃO DOS PDFs PARA EVITAR TIMEOUT NO PASSO FINAL
            $pdfSystemContent = $this->pdfService->generateSystemPdf($systemHtml);
            $pdfClientContent = $this->pdfService->generateClientPdf($clientHtml);

            // Salvar no Storage local temporário
            $clientFileName = 'client_' . uniqid() . '.pdf';
            $systemFileName = 'system_' . uniqid() . '.pdf';

            Storage::disk('local')->put("temp_propostas/{$clientFileName}", $pdfClientContent);
            Storage::disk('local')->put("temp_propostas/{$systemFileName}", $pdfSystemContent);

            session(['simulacao_atual' => [
                    'planIds' => $data['planIds'],
                    'lives' => $data['lives'],
                    'profile' => $data['profile'],
                    'nome' => $data['nome'] ?? null,
                    // 'raw_html' => $rawHtml, // Removing raw html since we now have the PDFs
                    'system_html' => $systemHtml,
                    'client_html' => $clientHtml,
                    'plans_without_internacao' => $plansWithoutInternacao,
                    'pdf_client_path' => "temp_propostas/{$clientFileName}",
                    'pdf_system_path' => "temp_propostas/{$systemFileName}",
                ]]);

            return response()->json([
                'success' => true,
                'client_html' => $clientHtml,
                'plans_without_internacao' => $plansWithoutInternacao
            ]);
        }
        catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(): View
    {
        try {
            $data = session('simulacao_atual');
            if (!$data || !isset($data['client_html'])) {
                return redirect()->route('home');
            }

            return view('proposta.index', [
                // We use client_html now instead of raw_html because we removed it to save session space
                'rawHtml' => $data['client_html'],
                'planosParsed' => [],
                'titulo' => 'Sua Proposta de Plano de Saúde',
                'payloadInfo' => 'Simulação gerada via Simulador Online.',
            ]);
        }
        catch (\Throwable $e) {
            return view('proposta.index', [
                'error' => $e->getMessage() . "\n\n" . $e->getTraceAsString(),
                'titulo' => 'Erro na Simulação',
                'payloadInfo' => null,
            ]);
        }
    }

    public function showSystemHtml(): View
    {
        try {
            $data = session('simulacao_atual');
            if (!$data || !isset($data['system_html'])) {
                abort(404, 'Nenhuma simulação encontrada na sessão.');
            }

            return view('proposta.pdf-template', [
                'content' => $data['system_html'],
                'titulo' => 'Proposta de Plano de Saúde (Individual)',
            ]);
        }
        catch (\Throwable $e) {
            abort(500, $e->getMessage());
        }
    }

    public function showClientHtml(): View
    {
        try {
            $data = session('simulacao_atual');
            if (!$data || !isset($data['client_html'])) {
                abort(404, 'Nenhuma simulação encontrada na sessão.');
            }

            return view('proposta.pdf-template', [
                'content' => $data['client_html'],
                'titulo' => 'Plano escolhido — Preços e Rede de Atendimento',
            ]);
        }
        catch (\Throwable $e) {
            abort(500, $e->getMessage());
        }
    }
}
