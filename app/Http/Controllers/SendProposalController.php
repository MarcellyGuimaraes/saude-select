<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\WhatsAppService;
use App\Support\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendProposalController extends Controller
{
    public function __construct(protected
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

            $clientPhone = $request->input('phone');

            if (!$clientPhone) {
                return response()->json(['success' => false, 'message' => 'Telefone não informado.'], 400);
            }

            // 1. Load Pre-generated PDFs from Storage
            $clientPdfPath = $data['pdf_client_path'] ?? null;
            $systemPdfPath = $data['pdf_system_path'] ?? null;

            if (!$clientPdfPath || !$systemPdfPath || !Storage::disk('local')->exists($clientPdfPath) || !Storage::disk('local')->exists($systemPdfPath)) {
                return response()->json(['success' => false, 'message' => 'PDFs não encontrados. Por favor, refaça a simulação.'], 400);
            }

            $pdfClientContent = Storage::disk('local')->get($clientPdfPath);
            $pdfSystemContent = Storage::disk('local')->get($systemPdfPath);

            $city = Location::format($data['city'] ?? null, $data['state_name'] ?? null) ?? 'Não informada';

            // 2. Send Email to Admin/System (Backup)
            $adminEmail = config('services.notificacoes.admin_email');
            Mail::to($adminEmail)->send(new \App\Mail\ProposalSystemMail($pdfSystemContent, 'proposta-sistema.pdf', $clientPhone, $city));

            // 3. WhatsApp Automation Flow
            $apiResult = ['success' => false];

            $apiResult = $this->whatsappService->sendPdf($clientPhone, $pdfClientContent, 'proposta-plano.pdf');

            $clientName = $data['nome'] ?? 'Cliente';

            // Action 3: Send Follow-up Message to Client
            $msgClient = "Olá, {$clientName}! 👋\n\n" .
                "O Dossiê BuscarPlanos " . date('Y') . " solicitado já está disponível acima. 📄\n\n" .
                "📍 Cidade da cotação: {$city}\n\n" .
                "Este documento apresenta o detalhamento técnico da seleção realizada, com os respectivos valores e especificações de rede.\n\n" .
                "A equipe de suporte analisará os critérios de aceitação para o perfil informado e entrará em contato para validar o match técnico, além de esclarecer eventuais dúvidas sobre carências ou procedimentos de adesão.\n\n" .
                "Agradecemos por utilizar a inteligência da BuscarPlanos. 🚀";

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
            // Get selected plans names
            $selectedPlanNamesStr = "Ver PDF anexo";

            $msgBroker = "📩 *NOVO LEAD CAPTURADO*\n\n" .
                "👤 *NOME:* {$clientName}\n" .
                "📱 *ORIGEM:* 🌐 WEB | 📍 *CIDADE:* {$city} | 👥 *VIDAS:* {$livesCount}\n" .
                "💼 *PERFIL:* {$profile}\n" .
                "🛡️ *STATUS DO PERFIL:* ✅ VALIDADO\n" .
                "📊 *PLANOS:* {$selectedPlanNamesStr}\n\n" .
                "💡 *VALIDAÇÃO " . date('Y') . ":* Cliente validado via sistema. O PDF gerado contém os valores e a rede.\n\n" .
                "📄 *[CLIQUE AQUI PARA O PDF COMPLETO]* (Ver Recibo Acima)\n\n" .
                "📲 *WhatsApp Cliente:* {$clientPhone}";

            $adminPhoneTarget = config('services.notificacoes.admin_whatsapp');

            $this->whatsappService->sendText($adminPhoneTarget, $msgBroker);
            // Also send the system PDF to admin
            $this->whatsappService->sendPdf($adminPhoneTarget, $pdfSystemContent, "Proposta_Sistema_{$clientPhone}.pdf");

            // Clean up temporary files
            Storage::disk('local')->delete([$clientPdfPath, $systemPdfPath]);

            return response()->json([
                'success' => true,
                'message' => 'Proposta enviada com sucesso!'
            ]);
        }
        catch (\Throwable $e) {
            Log::error("Erro ao enviar proposta: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
