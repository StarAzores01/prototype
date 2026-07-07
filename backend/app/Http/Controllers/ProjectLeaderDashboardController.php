<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ProjectLeaderDashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboards.project-leader');
    }
}
