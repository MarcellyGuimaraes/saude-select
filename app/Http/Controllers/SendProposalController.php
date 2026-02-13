<?php

namespace App\Http\Controllers;

use App\Mail\ProposalSystemMail;
use App\Services\PdfService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendProposalController extends Controller
{
    public function __construct(protected
        PdfService $pdfService, protected
        WhatsAppService $whatsappService
        )
    {
    }

    public function send(Request $request)
    {
        try {
            $data = session('simulacao_atual');
            if (!$data) {
                return response()->json(['success' => false, 'message' => 'Nenhuma simulação encontrada.'], 404);
            }

            // 1. Generate PDFs
            // We use the raw HTML already in session or re-generate if needed.
            // Based on PropostaController, we have 'client_html' and 'system_html' keys.
            $clientHtml = $data['client_html'] ?? null;
            $systemHtml = $data['system_html'] ?? null;

            if (!$clientHtml || !$systemHtml) {
                return response()->json(['success' => false, 'message' => 'Dados da simulação incompletos.'], 400);
            }

            $pdfSystemContent = $this->pdfService->generateSystemPdf($systemHtml);
            $pdfClientContent = $this->pdfService->generateClientPdf($clientHtml);

            // 2. Send Email to Admin/System
            // 2. Send Email to Admin/System
            $clientPhone = $request->input('phone');
            $adminEmail = 'renanldb93@gmail.com';
            Mail::to($adminEmail)->send(new \App\Mail\ProposalSystemMail($pdfSystemContent, 'proposta-sistema.pdf', $clientPhone));

            // 3. Send WhatsApp to Client (or prepared logic)
            // Expecting phone in request or session (if collected)

            $apiResult = ['success' => false];
            if ($clientPhone) {
                $apiResult = $this->whatsappService->sendPdf($clientPhone, $pdfClientContent, 'proposta-plano.pdf');
            }

            return response()->json([
                'success' => true,
                'message' => 'Proposta enviada com sucesso!',
                'debug_api_response' => $apiResult
            ]);

        }
        catch (\Throwable $e) {
            Log::error("Erro ao enviar proposta: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
