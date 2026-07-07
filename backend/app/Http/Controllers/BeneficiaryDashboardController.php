<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class BeneficiaryDashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboards.beneficiary');
    }
}
