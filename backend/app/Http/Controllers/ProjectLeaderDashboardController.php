<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ImpactAssessment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectLeaderDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $trainings = $request->user()->trainingsLed()
            ->withCount('participants')
            ->latest()
            ->get();

        $trainingIds = $trainings->pluck('id');

        $latestDocuments = Document::whereIn('training_id', $trainingIds)
            ->with('training')
            ->latest()
            ->take(5)
            ->get();

        $impactAssessmentCounts = ImpactAssessment::whereIn('training_id', $trainingIds)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('dashboards.project-leader', [
            'unreadNotifications' => $request->user()->appNotifications()->where('is_read', false)->count(),
            'trainings' => $trainings,
            'latestDocuments' => $latestDocuments,
            'impactAssessmentCounts' => $impactAssessmentCounts,
        ]);
    }
}
