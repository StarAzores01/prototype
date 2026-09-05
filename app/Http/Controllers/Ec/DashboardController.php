<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Program;
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

        // Trainers + Programs listed in the "Create Activity" modal (shared partial).
        $trainers = \App\Models\User::where('role', 'trainer')->where('is_active', true)->get(['id', 'first_name', 'last_name']);
        $programs = Program::orderBy('title')->get(['id', 'title']);

        return view('ec.dashboard', [
            'activePage'       => 'dashboard',
            'recentTrainings'  => $recentTrainings,
            'latestDocs'       => $latestDocs,
            'skills'           => $skills,
            'trainers'         => $trainers,
            'programs'         => $programs,
            'userFirstName'    => Auth::guard('web')->user()->first_name,
        ]);
    }
}
