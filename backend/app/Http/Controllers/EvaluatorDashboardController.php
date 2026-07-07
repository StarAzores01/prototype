<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class EvaluatorDashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboards.evaluator');
    }
}
