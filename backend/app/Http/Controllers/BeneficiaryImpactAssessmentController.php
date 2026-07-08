<?php

namespace App\Http\Controllers;

use App\Models\ImpactAssessment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BeneficiaryImpactAssessmentController extends Controller
{
    /**
     * List impact assessments requested from this beneficiary, across
     * all their trainings.
     */
    public function index(Request $request): View
    {
        $assessments = ImpactAssessment::where('user_id', $request->user()->id)
            ->with('training')
            ->latest()
            ->get();

        return view('beneficiary.impact-assessments.index', [
            'assessments' => $assessments,
        ]);
    }

    /**
     * Show the form to fill in a pending assessment's notes.
     */
    public function edit(Request $request, ImpactAssessment $impactAssessment): View
    {
        $this->authorizeOwnership($request, $impactAssessment);

        $impactAssessment->load('training');

        return view('beneficiary.impact-assessments.edit', [
            'assessment' => $impactAssessment,
        ]);
    }

    /**
     * Submit the assessment's notes.
     */
    public function update(Request $request, ImpactAssessment $impactAssessment): RedirectResponse
    {
        $this->authorizeOwnership($request, $impactAssessment);

        $validated = $request->validate([
            'notes' => ['required', 'string'],
        ]);

        $impactAssessment->update([
            'notes' => $validated['notes'],
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $impactAssessment->load('training');

        User::where('role', 'evaluator')->pluck('id')->each(
            fn (int $userId) => Notification::notify(
                $userId,
                'Impact Assessment Submitted',
                "{$request->user()->name} submitted an impact assessment for \"{$impactAssessment->training->title}\".",
                'impact_assessment_submitted'
            )
        );

        return redirect()
            ->route('beneficiary.impact-assessments.index')
            ->with('status', 'Thank you! Your impact assessment has been submitted.');
    }

    /**
     * A beneficiary may only view/submit their own assessment, and
     * only while it is still pending (not already submitted/reviewed).
     */
    private function authorizeOwnership(Request $request, ImpactAssessment $impactAssessment): void
    {
        abort_unless($impactAssessment->user_id === $request->user()->id, 403);
        abort_unless($impactAssessment->status === 'pending', 403, 'This assessment has already been submitted.');
    }
}
