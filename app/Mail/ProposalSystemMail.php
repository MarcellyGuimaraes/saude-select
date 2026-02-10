<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProposalSystemMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdfContent;
    public $pdfName;

    public function __construct($pdfContent, $pdfName = 'proposta.pdf')
    {
        $this->pdfContent = $pdfContent;
        $this->pdfName = $pdfName;
    }

    public function build()
    {
        return $this->subject('Nova Simulação - SaúdeSelect')
            ->view('emails.proposal_system')
            ->attachData($this->pdfContent, $this->pdfName, [
            'mime' => 'application/pdf',
        ]);
    }
}
