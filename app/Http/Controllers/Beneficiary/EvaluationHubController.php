<?php

namespace App\Http\Controllers\Beneficiary;

use App\Http\Controllers\Controller;
use App\Models\EvalForm;
use App\Models\EvalResponse;
use App\Models\ImpactAssessmentForm;
use App\Models\ImpactAssessmentResponse;
use App\Models\SkillsForm;
use App\Models\SkillsResponse;
use Illuminate\Support\Facades\Auth;

/**
 * New parent/landing page above evaluations.php, impact_assessment.php,
 * and skills.php. Each of those pages already runs the exact same
 * "forms sent to me, whether I've answered yet" query (see
 * {Evaluation,ImpactAssessment,Skills}Controller::index() in this
 * namespace) — tally() below is that shared shape, run once per form
 * type, not a new query.
 */
class EvaluationHubController extends Controller
{
    public function index()
    {
        $beneficiaryId = Auth::guard('beneficiary')->id();

        [$evalTotal, $evalPending] = $this->tally(EvalForm::class, EvalResponse::class, $beneficiaryId);
        [$iaTotal, $iaPending] = $this->tally(ImpactAssessmentForm::class, ImpactAssessmentResponse::class, $beneficiaryId);
        [$skillsTotal, $skillsPending] = $this->tally(SkillsForm::class, SkillsResponse::class, $beneficiaryId);

        $recentAssessmentForms = ImpactAssessmentForm::with('training')
            ->whereHas('training.participants', fn ($q) => $q->where('beneficiary_id', $beneficiaryId))
            ->whereNotNull('sent_at')
            ->orderByDesc('sent_at')
            ->limit(5)
            ->get();

        // Same per-form lookup ImpactAssessmentController::index() does, so the
        // "Answered / Pending" badge here matches what that page would show.
        foreach ($recentAssessmentForms as $form) {
            $form->myResponse = ImpactAssessmentResponse::where('form_id', $form->id)
                ->where('beneficiary_id', $beneficiaryId)
                ->first();
        }

        return view('beneficiary.evaluation', [
            'activePage'            => 'evaluation',
            'evalTotal'             => $evalTotal,
            'evalPending'           => $evalPending,
            'iaTotal'               => $iaTotal,
            'iaPending'             => $iaPending,
            'skillsTotal'           => $skillsTotal,
            'skillsPending'         => $skillsPending,
            'recentAssessmentForms' => $recentAssessmentForms,
        ]);
    }

    /** @return array{0: int, 1: int} [total forms sent to me, of which not yet answered] */
    private function tally(string $formModel, string $responseModel, int $beneficiaryId): array
    {
        $formIds = $formModel::whereHas('training.participants', fn ($q) => $q->where('beneficiary_id', $beneficiaryId))
            ->whereNotNull('sent_at')
            ->pluck('id');

        $total = $formIds->count();
        $answered = $responseModel::whereIn('form_id', $formIds)
            ->where('beneficiary_id', $beneficiaryId)
            ->distinct('form_id')
            ->count('form_id');

        return [$total, $total - $answered];
    }
}
