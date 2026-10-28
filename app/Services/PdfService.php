<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    /**
     * Generate the 'System' PDF (full profile, for admin/internal use).
     *
     * @param string $htmlContent
     * @param string|null $cidade Localização já formatada (Cidade - Estado)
     * @return string Binary PDF content
     */
    public function generateSystemPdf(string $htmlContent, ?string $cidade = null): string
    {
        return $this->generatePdf($htmlContent, 'Proposta de Plano de Saúde (Individual)', $cidade);
    }

    /**
     * Generate the 'Client' PDF (limited info, for the customer).
     *
     * @param string $htmlContent
     * @param string|null $cidade Localização já formatada (Cidade - Estado)
     * @return string Binary PDF content
     */
    public function generateClientPdf(string $htmlContent, ?string $cidade = null): string
    {
        return $this->generatePdf($htmlContent, 'Plano escolhido — Preços e Rede de Atendimento', $cidade);
    }

    /**
     * Internal generic PDF generation.
     *
     * @param string $htmlContent
     * @param string $title
     * @param string|null $cidade
     * @return string
     */
    protected function generatePdf(string $htmlContent, string $title, ?string $cidade = null): string
    {
        // We reuse the existing view 'proposta.pdf-template'
        // which Step 5 / PropostaController was already using conceptually.
        $pdf = Pdf::loadView('proposta.pdf-template', [
            'content' => $htmlContent,
            'titulo' => $title,
            'cidade' => $cidade,
        ]);

        // Optional: Global PDF settings if needed
        // $pdf->setPaper('a4', 'portrait');

        return $pdf->output();
    }
}
