<?php

namespace App\Http\Controllers;

use App\Models\ImpactAssessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EvaluatorImpactAssessmentController extends Controller
{
    /**
     * List submitted impact assessments awaiting review, across all
     * trainings (evaluators are not scoped to specific trainings).
     */
    public function index(): View
    {
        $assessments = ImpactAssessment::where('status', 'submitted')
            ->with(['training', 'user'])
            ->latest('submitted_at')
            ->get();

        return view('evaluator.impact-assessments.index', [
            'assessments' => $assessments,
        ]);
    }

    /**
     * View one submitted assessment's notes.
     */
    public function show(ImpactAssessment $impactAssessment): View
    {
        $impactAssessment->load(['training', 'user']);

        return view('evaluator.impact-assessments.show', [
            'assessment' => $impactAssessment,
        ]);
    }

    /**
     * Mark a submitted assessment as reviewed.
     */
    public function markReviewed(ImpactAssessment $impactAssessment): RedirectResponse
    {
        abort_unless($impactAssessment->status === 'submitted', 403, 'Only submitted assessments can be marked reviewed.');

        $impactAssessment->update(['status' => 'reviewed']);

        return redirect()
            ->route('evaluator.impact-assessments.index')
            ->with('status', 'Impact assessment marked as reviewed.');
    }
}
