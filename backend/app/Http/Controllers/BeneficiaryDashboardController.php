<?php

namespace App\Http\Controllers;

use App\Models\EvaluationForm;
use App\Models\ImpactAssessment;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BeneficiaryDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $participations = Participant::where('user_id', $request->user()->id)
            ->with('training')
            ->latest()
            ->take(5)
            ->get();

        $trainingIds = Participant::where('user_id', $request->user()->id)
            ->where('status', '!=', 'dropped')
            ->pluck('training_id');

        $pendingForms = EvaluationForm::whereIn('training_id', $trainingIds)
            ->where('status', 'published')
            ->whereDoesntHave('responses', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with('training')
            ->get();

        $pendingImpactAssessmentsCount = ImpactAssessment::where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->count();

        return view('dashboards.beneficiary', [
            'unreadNotifications' => $request->user()->appNotifications()->where('is_read', false)->count(),
            'participations' => $participations,
            'pendingForms' => $pendingForms,
            'pendingImpactAssessmentsCount' => $pendingImpactAssessmentsCount,
        ]);
    }
}
