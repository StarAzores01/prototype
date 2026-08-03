<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\SkillsUtilization;
use App\Models\Training;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $recentTrainings = Training::with('trainer')
            ->withCount('participants as enrolled')
            ->orderByDesc('created_at')
            ->get();

        $latestDocs = Document::with('training')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $skills = [
            'personal'   => round((float) (SkillsUtilization::avg('personal_use_pct') ?? 72), 1),
            'income'     => round((float) (SkillsUtilization::avg('income_gen_pct') ?? 55), 1),
            'employment' => round((float) (SkillsUtilization::avg('employment_pct') ?? 38), 1),
        ];

        // Trainers listed in the "Create Training" modal's Project Leader select.
        $trainers = \App\Models\User::where('role', 'trainer')->where('is_active', true)->get(['id', 'first_name', 'last_name']);

        return view('ec.dashboard', [
            'activePage'       => 'dashboard',
            'recentTrainings'  => $recentTrainings,
            'latestDocs'       => $latestDocs,
            'skills'           => $skills,
            'trainers'         => $trainers,
            'userFirstName'    => Auth::guard('web')->user()->first_name,
        ]);
    }
}
