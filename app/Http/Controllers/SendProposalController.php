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

            // 2. Send Email to Admin/System (Backup)
            $clientPhone = $request->input('phone');
            $adminEmail = 'renanldb93@gmail.com';
            Mail::to($adminEmail)->send(new \App\Mail\ProposalSystemMail($pdfSystemContent, 'proposta-sistema.pdf', $clientPhone));

            // 3. WhatsApp Automation Flow
            $apiResult = ['success' => false];

            if ($clientPhone) {
                $apiResult = $this->whatsappService->sendPdf($clientPhone, $pdfClientContent, 'proposta-plano.pdf');

                // Action 3: Send Follow-up Message to Client
                $msgClient = "O Dossiê SaúdeSelect 2026 solicitado já está disponível acima. 📄\n\n" .
                    "Este documento apresenta o detalhamento técnico da seleção realizada, com os respectivos valores e especificações de rede.\n\n" .
                    "A equipe de suporte analisará os critérios de aceitação para o perfil informado e entrará em contato para validar o match técnico, além de esclarecer eventuais dúvidas sobre carências ou procedimentos de adesão.\n\n" .
                    "Agradecemos por utilizar a inteligência da SaúdeSelect. 🚀";

                $this->whatsappService->sendText($clientPhone, $msgClient);

                // Action 4: Broker Alert (Inteligência Pós-Clique)
                // Gather Data
                $profile = ucfirst($data['profile'] ?? 'N/A');
                $livesCount = 0;
                if (isset($data['lives']) && is_array($data['lives'])) {
                    foreach ($data['lives'] as $qtd) {
                        if (is_numeric($qtd)) {
                            $livesCount += $qtd;
                        }
                    }
                }
                $city = $data['city'] ?? 'N/A'; // Default to N/A if not found

                // Get selected plans names
                $selectedPlanNamesStr = "Ver PDF anexo";

                $msgBroker = "📩 *NOVO LEAD CAPTURADO*\n\n" .
                    "👤 *HISTÓRICO:* 🟢 PRIMEIRA CONSULTA\n" .
                    "📱 *ORIGEM:* 🌐 WEB | 📍 *CIDADE:* {$city} | 👥 *VIDAS:* {$livesCount}\n" .
                    "💼 *PERFIL:* {$profile}\n" .
                    "🛡️ *STATUS DO PERFIL:* ✅ VALIDADO\n" .
                    "🏥 *HOSPITAL ALVO:* (Ver PDF) | 📊 *PLANOS:* {$selectedPlanNamesStr}\n\n" .
                    "💡 *VALIDAÇÃO 2026:* Cliente validado via sistema. O PDF gerado contém os valores e a rede.\n\n" .
                    "📄 *[CLIQUE AQUI PARA O PDF COMPLETO]* (Ver Recibo Acima)\n\n" .
                    "📲 *WhatsApp Cliente:* {$clientPhone}";

                $adminPhoneTarget = '5521999999999'; // Admin Phone (Same as Sender)

                $this->whatsappService->sendText($adminPhoneTarget, $msgBroker);
                // Also send the system PDF to admin
                $this->whatsappService->sendPdf($adminPhoneTarget, $pdfSystemContent, "Proposta_Sistema_{$clientPhone}.pdf");
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
