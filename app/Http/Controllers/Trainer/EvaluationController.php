<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\EvalForm;
use App\Models\EvalResponse;
use App\Models\Evaluation;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluationController extends Controller
{
    public function index(Request $request)
    {
        $trainerId = Auth::guard('web')->id();

        $viewTrainingId = (int) $request->query('responses', 0);

        if ($viewTrainingId) {
            $training = Training::where('id', $viewTrainingId)->visibleToTrainer($trainerId)->first();

            if ($training) {
                $form = EvalForm::where('training_id', $viewTrainingId)->first();

                if ($form) {
                    return $this->responsesView($form, $training);
                }
            }
        }

        return $this->mainView($trainerId);
    }

    private function mainView(int $trainerId)
    {
        $sentForms = EvalForm::with('training')
            ->whereHas('training', fn ($t) => $t->visibleToTrainer($trainerId))
            ->whereNotNull('sent_at')
            ->orderByDesc('sent_at')
            ->get();

        $avgRatingRaw = Evaluation::whereHas('training', fn ($t) => $t->visibleToTrainer($trainerId))
            ->whereNotNull('rating')
            ->avg('rating');
        $avgRating = $avgRatingRaw !== null ? round((float) $avgRatingRaw, 1) : null;

        $submitted = Evaluation::whereHas('training', fn ($t) => $t->visibleToTrainer($trainerId))
            ->where('status', 'Submitted')->count();

        $pending = Evaluation::whereHas('training', fn ($t) => $t->visibleToTrainer($trainerId))
            ->where('status', 'Pending')->count();

        $evalData = Training::visibleToTrainer($trainerId)
            ->withCount('participants as total_pax')
            ->withCount(['evaluations as submitted' => fn ($q) => $q->where('status', 'Submitted')])
            ->withCount(['evaluations as pending' => fn ($q) => $q->where('status', 'Pending')])
            ->withAvg(['evaluations as avg_rating' => fn ($q) => $q->whereNotNull('rating')], 'rating')
            ->orderByDesc('date_start')
            ->get();

        $trainingIds = $evalData->pluck('id');
        $responseCounts = EvalResponse::join('eval_forms', 'eval_forms.id', '=', 'eval_responses.form_id')
            ->whereIn('eval_forms.training_id', $trainingIds)
            ->selectRaw('eval_forms.training_id, count(*) as cnt')
            ->groupBy('eval_forms.training_id')
            ->pluck('cnt', 'eval_forms.training_id');

        foreach ($evalData as $row) {
            $row->response_count = (int) ($responseCounts[$row->id] ?? 0);
        }

        return view('trainer.evaluations', [
            'activePage' => 'evaluations',
            'mode'       => 'list',
            'sentForms'  => $sentForms,
            'avgRating'  => $avgRating,
            'submitted'  => $submitted,
            'pending'    => $pending,
            'evalData'   => $evalData,
        ]);
    }

    private function responsesView(EvalForm $form, Training $training)
    {
        $responses = EvalResponse::with('beneficiary')
            ->where('form_id', $form->id)
            ->orderByDesc('submitted_at')
            ->get();

        return view('trainer.evaluations', [
            'activePage'   => 'evaluations',
            'mode'         => 'responses',
            'viewForm'     => $form,
            'viewTraining' => $training,
            'responses'    => $responses,
        ]);
    }
}
