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
    public $phone;

    public function __construct($pdfContent, $pdfName = 'proposta.pdf', $phone = null)
    {
        $this->pdfContent = $pdfContent;
        $this->pdfName = $pdfName;
        $this->phone = $phone;
    }

    public function build()
    {
        $subject = 'Nova Simulação - BuscarPlanos';
        if ($this->phone) {
            $subject .= " - Cliente: {$this->phone}";
        }

        return $this->subject($subject)
            ->view('emails.proposal_system')
            ->attachData($this->pdfContent, $this->pdfName, [
            'mime' => 'application/pdf',
        ]);
    }
}
