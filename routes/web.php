<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PropostaController;
use App\Http\Controllers\StepController;
use App\Services\SimuladorOnlineService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class , 'index'])->name('home');

Route::get('/admin', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/step/{step}', [StepController::class , 'show'])->name('step.show');
Route::get('/step-final', [StepController::class , 'final'])->name('step.final');

// API para buscar hospitais (Step 1)
Route::get('/api/hospitais/buscar', [StepController::class , 'buscarHospitais'])->name('api.hospitais.buscar');

// API para buscar planos (Step 4)
Route::post('/api/planos/buscar', [StepController::class , 'buscarPlanos'])->name('api.planos.buscar');

// Rotas de Proposta
Route::controller(PropostaController::class)->group(function () {
    Route::get('/proposta', 'index')->name('proposta.index');
    Route::post('/proposta/gerar', 'gerar')->name('proposta.gerar');
    Route::get('/proposta/sistema', 'showSystemHtml')->name('proposta.sistema');
    Route::get('/proposta/cliente', 'showClientHtml')->name('proposta.cliente');
});

require __DIR__ . '/settings.php';
