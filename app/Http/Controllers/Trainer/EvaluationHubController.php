<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\EvalForm;
use App\Models\Evaluation;
use App\Models\SkillsUtilization;
use Illuminate\Support\Facades\Auth;

/**
 * New parent/landing page above evaluations.php and skills.php — no
 * impact_assessment.php exists for Trainer, so this hub only covers the 2
 * features that apply to this role. Every number is the exact same query
 * EvaluationController::mainView() / SkillsController::mainView() already
 * run, just scoped the same way (Training::visibleToTrainer()) and
 * surfaced here as an overview.
 */
class EvaluationHubController extends Controller
{
    public function index()
    {
        $trainerId = Auth::guard('web')->id();

        $sentForms = EvalForm::whereHas('training', fn ($t) => $t->visibleToTrainer($trainerId))
            ->whereNotNull('sent_at')
            ->count();

        $avgRatingRaw = Evaluation::whereHas('training', fn ($t) => $t->visibleToTrainer($trainerId))
            ->whereNotNull('rating')
            ->avg('rating');
        $avgRating = $avgRatingRaw !== null ? round((float) $avgRatingRaw, 1) : null;

        $submitted = Evaluation::whereHas('training', fn ($t) => $t->visibleToTrainer($trainerId))
            ->where('status', 'Submitted')->count();
        $pending = Evaluation::whereHas('training', fn ($t) => $t->visibleToTrainer($trainerId))
            ->where('status', 'Pending')->count();

        $skills = SkillsUtilization::whereHas('training', fn ($t) => $t->visibleToTrainer($trainerId))
            ->selectRaw('AVG(personal_use_pct) as personal, AVG(income_gen_pct) as income, AVG(employment_pct) as employment')
            ->first();

        return view('trainer.evaluation', [
            'activePage'     => 'evaluation',
            'sentForms'      => $sentForms,
            'avgRating'      => $avgRating,
            'submitted'      => $submitted,
            'pending'        => $pending,
            'skillsOverview' => [
                'personal'   => round((float) ($skills->personal ?? 0), 1),
                'income'     => round((float) ($skills->income ?? 0), 1),
                'employment' => round((float) ($skills->employment ?? 0), 1),
            ],
        ]);
    }
}
