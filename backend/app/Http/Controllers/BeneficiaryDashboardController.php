<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class BeneficiaryDashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboards.beneficiary', [
            'unreadNotifications' => $request->user()->appNotifications()->where('is_read', false)->count(),
        ]);
    }
}
