<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\StepController;
use App\Services\SimuladorOnlineService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/admin', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/step/{step}', [StepController::class, 'show'])->name('step.show');
Route::get('/step-final', [StepController::class, 'final'])->name('step.final');

// API para buscar hospitais (Step 1)
Route::get('/api/hospitais/buscar', [StepController::class, 'buscarHospitais'])->name('api.hospitais.buscar');

// API para buscar planos (Step 4)
Route::post('/api/planos/buscar', [StepController::class, 'buscarPlanos'])->name('api.planos.buscar');

// Rota de teste: POST em /simulacao/nova (conforme curl)
Route::get('/test/simulador-adesao', function () {
    try {
        $service = app(SimuladorOnlineService::class);
        $rawHtml = $service->getAdesaoRawHtml();

        return view('test.simulador-resposta', [
            'rawHtml' => $rawHtml,
            'planosParsed' => [],
            'titulo' => 'Teste: Resposta POST /simulacao/nova',
            'payloadInfo' => 'POST /simulacao/nova (após login).',
        ]);
    } catch (\Throwable $e) {
        return view('test.simulador-resposta', [
            'error' => $e->getMessage()."\n\n".$e->getTraceAsString(),
            'titulo' => 'Teste: Resposta POST /simulacao/nova',
            'payloadInfo' => null,
        ]);
    }
})->name('test.simulador-adesao');

Route::get('/test/simulador-adesao/pdf', function () {
    try {
        $service = app(SimuladorOnlineService::class);
        $rawHtml = $service->getAdesaoRawHtml();
        $content = SimuladorOnlineService::extractProposalContent(
            $rawHtml,
            config('services.simulador_online.base_url', 'https://app.simuladoronline.com')
        );

        $pdf = Pdf::loadView('test.proposta-pdf', [
            'content' => $content,
            'titulo' => 'Proposta de Plano de Saúde (Individual)',
        ])->setPaper('a4', 'portrait');

        return $pdf->download('proposta-plano-saude-sistema.pdf');
    } catch (\Throwable $e) {
        abort(500, $e->getMessage());
    }
})->name('test.simulador-adesao.pdf');

Route::get('/test/simulador-adesao/pdf-cliente', function () {
    try {
        $baseUrl = config('services.simulador_online.base_url', 'https://app.simuladoronline.com');
        $service = app(SimuladorOnlineService::class);
        $rawHtml = $service->getAdesaoRawHtml();
        $content = SimuladorOnlineService::extractClientProposalContent($rawHtml, $baseUrl);

        $pdf = Pdf::loadView('test.proposta-pdf', [
            'content' => $content,
            'titulo' => 'Plano escolhido — Preços e Rede de Atendimento',
        ])->setPaper('a4', 'portrait');

        return $pdf->download('proposta-plano-saude-cliente.pdf');
    } catch (\Throwable $e) {
        abort(500, $e->getMessage());
    }
})->name('test.simulador-adesao.pdf-cliente');

require __DIR__.'/settings.php';
