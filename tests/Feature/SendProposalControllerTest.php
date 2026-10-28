<?php

use App\Mail\ProposalSystemMail;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\mock;

beforeEach(function () {
    Storage::fake('local');
    Mail::fake();

    Storage::disk('local')->put('propostas/cliente.pdf', 'pdf-cliente');
    Storage::disk('local')->put('propostas/sistema.pdf', 'pdf-sistema');

    session(['simulacao_atual' => [
        'nome' => 'Maria',
        'profile' => 'pf',
        'lives' => ['0-18' => 1],
        'city' => 'Niterói',
        'state_name' => 'Rio de Janeiro',
        'pdf_client_path' => 'propostas/cliente.pdf',
        'pdf_system_path' => 'propostas/sistema.pdf',
    ]]);
});

function expectTeamWhatsapp(string $phone): void
{
    $whatsapp = mock(WhatsAppService::class);
    $whatsapp->shouldReceive('sendPdf')->with('21988887777', 'pdf-cliente', 'proposta-plano.pdf')->once()->andReturn(['success' => true]);
    $whatsapp->shouldReceive('sendText')->with('21988887777', Mockery::any())->once()->andReturn(['success' => true]);
    $whatsapp->shouldReceive('sendText')->with($phone, Mockery::pattern('/NOVO LEAD CAPTURADO/'))->once()->andReturn(['success' => true]);
    $whatsapp->shouldReceive('sendPdf')->with($phone, 'pdf-sistema', Mockery::any())->once()->andReturn(['success' => true]);
}

test('envia para os destinos padrao da equipe', function () {
    expectTeamWhatsapp('5521988965001');

    $this->postJson(route('proposta.enviar'), ['phone' => '21988887777'])
        ->assertOk()
        ->assertJson(['success' => true]);

    Mail::assertSent(ProposalSystemMail::class, fn ($mail) => $mail->hasTo('buscarplanos.oficial@gmail.com'));
});

test('envia para os destinos configurados no ambiente', function () {
    config([
        'services.notificacoes.admin_email' => 'outro@example.com',
        'services.notificacoes.admin_whatsapp' => '5511900001111',
    ]);
    expectTeamWhatsapp('5511900001111');

    $this->postJson(route('proposta.enviar'), ['phone' => '21988887777'])
        ->assertOk()
        ->assertJson(['success' => true]);

    Mail::assertSent(ProposalSystemMail::class, fn ($mail) => $mail->hasTo('outro@example.com'));
    Mail::assertNotSent(ProposalSystemMail::class, fn ($mail) => $mail->hasTo('buscarplanos.oficial@gmail.com'));
});

test('envia a localizacao no alerta, no e-mail e na mensagem ao cliente', function () {
    $whatsapp = mock(WhatsAppService::class);
    $whatsapp->shouldReceive('sendPdf')->andReturn(['success' => true]);
    $whatsapp->shouldReceive('sendText')->with('21988887777', Mockery::pattern('/Cidade da cotação: Niterói - Rio de Janeiro/'))->once()->andReturn(['success' => true]);
    $whatsapp->shouldReceive('sendText')->with('5521988965001', Mockery::pattern('/CIDADE:\* Niterói - Rio de Janeiro/'))->once()->andReturn(['success' => true]);

    $this->postJson(route('proposta.enviar'), ['phone' => '21988887777'])->assertOk();

    Mail::assertSent(ProposalSystemMail::class, function ($mail) {
        $mail->assertSeeInHtml('Niterói - Rio de Janeiro');

        return true;
    });
});

test('mostra a localizacao como nao informada quando a sessao nao tem cidade nem estado', function () {
    session(['simulacao_atual' => array_merge(session('simulacao_atual'), ['city' => null, 'state_name' => null])]);

    $whatsapp = mock(WhatsAppService::class);
    $whatsapp->shouldReceive('sendPdf')->andReturn(['success' => true]);
    $whatsapp->shouldReceive('sendText')->with('21988887777', Mockery::any())->once()->andReturn(['success' => true]);
    $whatsapp->shouldReceive('sendText')->with('5521988965001', Mockery::pattern('/CIDADE:\* Não informada/'))->once()->andReturn(['success' => true]);

    $this->postJson(route('proposta.enviar'), ['phone' => '21988887777'])->assertOk();

    Mail::assertSent(ProposalSystemMail::class, function ($mail) {
        $mail->assertSeeInHtml('Não informada');

        return true;
    });
});
