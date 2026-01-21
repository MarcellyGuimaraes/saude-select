<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StepController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/admin', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/step/{step}', [StepController::class, 'show'])->name('step.show');
Route::get('/step-final', [StepController::class, 'final'])->name('step.final');

require __DIR__.'/settings.php';
