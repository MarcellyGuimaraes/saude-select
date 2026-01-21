<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class StepController extends Controller
{
    public function show(int $step): View
    {
        // Valida se o step é válido (1 a 5)
        if ($step < 1 || $step > 5) {
            abort(404);
        }

        return view("steps.step-{$step}");
    }

    public function final(): View
    {
        return view('steps.step-final');
    }
}
