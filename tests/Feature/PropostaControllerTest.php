<?php

use App\Services\PdfService;
use App\Services\SimuladorOnlineService;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\mock;

beforeEach(function () {
    Storage::fake('local');

    mock(SimuladorOnlineService::class)
        ->shouldReceive('getSimulationRawHtml')
        ->andReturn('<html><body><div class="conteudo">Proposta</div></body></html>');
});

function payloadGeracao(array $extra = []): array
{
    return array_merge([
        'planIds' => [1],
        'lives' => ['0-18' => 1],
        'profile' => 'pf',
        'nome' => 'Maria',
    ], $extra);
}

test('guarda cidade e estado na sessao e repassa a localizacao aos PDFs', function () {
    $pdf = mock(PdfService::class);
    $pdf->shouldReceive('generateSystemPdf')->with(Mockery::any(), 'Niterói - Rio de Janeiro')->once()->andReturn('pdf-sistema');
    $pdf->shouldReceive('generateClientPdf')->with(Mockery::any(), 'Niterói - Rio de Janeiro')->once()->andReturn('pdf-cliente');

    $this->postJson(route('proposta.gerar'), payloadGeracao(['city' => 'Niterói', 'state_name' => 'Rio de Janeiro']))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(session('simulacao_atual'))
        ->city->toBe('Niterói')
        ->state_name->toBe('Rio de Janeiro');
});

test('gera a proposta sem localizacao', function () {
    $pdf = mock(PdfService::class);
    $pdf->shouldReceive('generateSystemPdf')->with(Mockery::any(), null)->once()->andReturn('pdf-sistema');
    $pdf->shouldReceive('generateClientPdf')->with(Mockery::any(), null)->once()->andReturn('pdf-cliente');

    $this->postJson(route('proposta.gerar'), payloadGeracao())
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(session('simulacao_atual'))
        ->city->toBeNull()
        ->state_name->toBeNull();
});

test('pre-visualizacao mostra a linha da localizacao', function () {
    session(['simulacao_atual' => [
        'system_html' => '<p>Proposta sistema</p>',
        'client_html' => '<p>Proposta cliente</p>',
        'city' => 'Niterói',
        'state_name' => 'Rio de Janeiro',
    ]]);

    $this->get(route('proposta.sistema'))->assertOk()->assertSee('Niterói - Rio de Janeiro');
    $this->get(route('proposta.cliente'))->assertOk()->assertSee('Niterói - Rio de Janeiro');
});

test('pre-visualizacao omite a linha sem localizacao', function () {
    session(['simulacao_atual' => [
        'system_html' => '<p>Proposta sistema</p>',
        'client_html' => '<p>Proposta cliente</p>',
    ]]);

    $this->get(route('proposta.sistema'))->assertOk()->assertDontSee('Cidade:');
});
