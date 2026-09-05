<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\EvalForm;
use App\Models\Evaluation;
use App\Models\ImpactAssessment;
use App\Models\SkillsForm;
use App\Models\SkillsResponse;

/**
 * New parent/landing page above the 3 existing, unchanged sub-pages
 * (evaluations.php, impact_assessment.php, skills.php) — a summary
 * dashboard, not a replacement. Every number here is the same query its
 * own sub-page (or, for the pending-evaluations count, EcLayoutComposer's
 * sidebar badge) already runs.
 */
class EvaluationHubController extends Controller
{
    public function index()
    {
        return view('ec.evaluation', [
            'activePage'         => 'evaluation',
            // Same query as EcLayoutComposer's sidebar badge.
            'pendingEvaluations' => Evaluation::where('status', 'Pending')->count(),
            'evalFormsSent'      => EvalForm::whereNotNull('sent_at')->count(),

            // Same base query as ImpactAssessmentController::evaluatorsTab(), just recent + limited.
            'recentAssessments'  => ImpactAssessment::with(['training', 'evaluator'])
                ->orderByDesc('submitted_at')
                ->limit(5)
                ->get(),
            'pendingAssessments' => ImpactAssessment::where('status', 'Submitted')->count(),

            // SkillsController's page is form/response counts, not utilization
            // percentages (that averaging only happens on the Trainer side).
            'skillsFormsSent'    => SkillsForm::whereNotNull('sent_at')->count(),
            'skillsResponses'    => SkillsResponse::count(),
        ]);
    }
}
