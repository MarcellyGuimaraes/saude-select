<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send a PDF to a phone number using Uazapi (Base64).
     * 
     * @param string $phone
     * @param string $pdfContent Binary PDF content
     * @param string $filename
     * @return bool
     */
    public function sendPdf(string $phone, string $pdfContent, string $filename): array
    {
        $baseUrl = config('services.uazapi.base_url', env('UAZAPI_BASE_URL'));
        $token = config('services.uazapi.token', env('UAZAPI_TOKEN'));

        if (!$baseUrl || !$token) {
            Log::warning("WhatsAppService: Uazapi keys not configured.");
            return ['success' => false, 'error' => 'Configuration missing'];
        }

        // Format phone
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) < 12) {
            $phone = '55' . $phone;
        }

        // Convert PDF to Base64 with Data URI scheme
        $base64 = base64_encode($pdfContent);
        $fileData = "data:application/pdf;base64,{$base64}";

        // Endpoint from user: https://free.uazapi.com/send/media
        // Assuming base_url is https://free.uazapi.com, we append /send/media
        $endpoint = rtrim($baseUrl, '/') . '/send/media';

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'token' => $token, // User specified 'token' header
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($endpoint, [
                'number' => $phone,
                'type' => 'document',
                'file' => $fileData, // Trying Base64 in 'file' field
                'docName' => $filename,
                'text' => 'Segue a proposta de plano de saúde solicitada.'
            ]);

            $body = $response->json() ?? $response->body();

            if ($response->successful()) {
                Log::info("WhatsAppService: Sent PDF to {$phone}", ['response' => $body]);
                return ['success' => true, 'response' => $body];
            }
            else {
                Log::error("WhatsAppService: Failed to send PDF. Status: " . $response->status(), [
                    'body' => $body
                ]);
                return ['success' => false, 'response' => $body, 'status' => $response->status()];
            }
        }
        catch (\Throwable $e) {
            Log::error("WhatsAppService: Exception sending PDF: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate a WhatsApp Click-to-Chat link with a pre-filled message.
     * This is a fallback if direct sending is not configured.
     * 
     * @param string $phone
     * @param string $text
     * @return string
     */
    public function generateLink(string $phone, string $text): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        $encodedText = urlencode($text);

        return "https://wa.me/{$phone}?text={$encodedText}";
    }
}
