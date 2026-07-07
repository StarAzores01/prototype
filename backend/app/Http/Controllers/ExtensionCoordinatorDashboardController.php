<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ExtensionCoordinatorDashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboards.extension-coordinator');
    }
}
