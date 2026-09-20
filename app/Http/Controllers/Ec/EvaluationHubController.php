<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\EvalForm;
use App\Models\ImpactAssessment;
use App\Models\SkillsForm;
use App\Models\SkillsUtilization;

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
        // EC-wide averages across every training with a skills_utilization
        // row — see SkillsUtilization::recomputeForTraining() (unscoped,
        // unlike the Trainer hub's visibleToTrainer()-scoped version).
        $skills = SkillsUtilization::selectRaw('AVG(personal_use_pct) as personal, AVG(income_gen_pct) as income, AVG(employment_pct) as employment, AVG(community_service_pct) as community, AVG(training_application_pct) as application, AVG(other_pct) as other')
            ->first();

        return view('ec.evaluation', [
            'activePage'         => 'evaluation',
            'evalFormsSent'      => EvalForm::whereNotNull('sent_at')->count(),

            // Same base query as ImpactAssessmentController::evaluatorsTab(), just recent + limited.
            'recentAssessments'  => ImpactAssessment::with(['training', 'evaluator'])
                ->orderByDesc('submitted_at')
                ->limit(3)
                ->get(),
            'pendingAssessments' => ImpactAssessment::where('status', 'Submitted')->count(),

            // SkillsController's page is form/response counts, not utilization
            // percentages (that averaging only happens on the Trainer side).
            'skillsFormsSent'    => SkillsForm::whereNotNull('sent_at')->count(),
            'skillsOverview'     => [
                'personal'    => round((float) ($skills->personal ?? 0), 1),
                'income'      => round((float) ($skills->income ?? 0), 1),
                'employment'  => round((float) ($skills->employment ?? 0), 1),
                'community'   => round((float) ($skills->community ?? 0), 1),
                'application' => round((float) ($skills->application ?? 0), 1),
                'other'       => round((float) ($skills->other ?? 0), 1),
            ],
        ]);
    }
}
