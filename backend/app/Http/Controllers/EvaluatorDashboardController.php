<?php

namespace App\Http\Controllers;

use App\Models\ImpactAssessment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluatorDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $assessmentCounts = ImpactAssessment::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $awaitingReview = ImpactAssessment::where('status', 'submitted')
            ->with(['training', 'user'])
            ->latest('submitted_at')
            ->take(5)
            ->get();

        return view('dashboards.evaluator', [
            'unreadNotifications' => $request->user()->appNotifications()->where('is_read', false)->count(),
            'assessmentCounts' => $assessmentCounts,
            'awaitingReview' => $awaitingReview,
        ]);
    }
}
