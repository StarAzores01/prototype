<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ImpactAssessment;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExtensionCoordinatorDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $trainings = Training::with(['creator', 'projectLeader'])
            ->withCount('participants')
            ->latest()
            ->get();

        $latestDocuments = Document::with('training')->latest()->take(5)->get();

        $impactAssessmentCounts = ImpactAssessment::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('dashboards.extension-coordinator', [
            'unreadNotifications' => $request->user()->appNotifications()->where('is_read', false)->count(),
            'trainings' => $trainings,
            'latestDocuments' => $latestDocuments,
            'impactAssessmentCounts' => $impactAssessmentCounts,
        ]);
    }
}
