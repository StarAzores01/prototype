<?php

namespace App\Http\Controllers\Evaluator;

use App\Http\Controllers\Controller;
use App\Models\ImpactAssessment;
use Illuminate\Support\Facades\Auth;

/**
 * New parent/landing page above impact_assessment.php — the only one of
 * the 3 features that applies to the Evaluator role (no evaluations.php or
 * skills.php exist for this role). Same base query as
 * ImpactAssessmentController::index(), just recent + limited plus a
 * status tally.
 */
class EvaluationHubController extends Controller
{
    public function index()
    {
        $evaluatorId = Auth::guard('web')->id();

        return view('evaluator.evaluation', [
            'activePage'        => 'evaluation',
            'recentAssessments' => ImpactAssessment::with('training.program')
                ->where('evaluator_id', $evaluatorId)
                ->orderByDesc('submitted_at')
                ->limit(5)
                ->get(),
            'submittedCount' => ImpactAssessment::where('evaluator_id', $evaluatorId)->where('status', 'Submitted')->count(),
            'reviewedCount'  => ImpactAssessment::where('evaluator_id', $evaluatorId)->where('status', 'Reviewed')->count(),
        ]);
    }
}
