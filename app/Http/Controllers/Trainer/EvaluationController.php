<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\EvalForm;
use App\Models\EvalResponse;
use App\Models\Evaluation;
use App\Models\Notification;
use App\Models\Participant;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EvaluationController extends Controller
{
    public function index(Request $request)
    {
        $trainerId = Auth::guard('web')->id();

        $viewTrainingId = (int) $request->query('responses', 0);
        $editTrainingId = (int) $request->query('edit_form', 0);

        if ($viewTrainingId) {
            $training = Training::where('id', $viewTrainingId)->visibleToTrainer($trainerId)->first();

            if ($training) {
                $form = EvalForm::where('training_id', $viewTrainingId)->first();

                if ($form) {
                    return $this->responsesView($form, $training);
                }
            }
        }

        if ($editTrainingId) {
            $training = Training::where('id', $editTrainingId)->visibleToTrainer($trainerId)->first();
            if ($training) {
                return $this->formBuilderView($training);
            }
        }

        return $this->mainView($trainerId);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'save_form' => $this->saveForm($request),
            'send_form' => $this->sendForm($request),
            default     => back(),
        };
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
            ->with(['evalForms' => fn ($q) => $q->withCount('responses')])
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

    /** Same form builder as Ec\EvaluationController — scoped to activities this trainer can see. */
    private function formBuilderView(Training $training)
    {
        $form = EvalForm::where('training_id', $training->id)->first();

        return view('trainer.evaluations', [
            'activePage'   => 'evaluations',
            'mode'         => 'builder',
            'editTraining' => $training,
            'editForm'     => $form,
        ]);
    }

    /** Identical field-building logic to Ec\EvaluationController::saveForm(), gated to this trainer's own activities. */
    private function saveForm(Request $request)
    {
        $data = Validator::make($request->all(), [
            'training_id'      => 'required|integer|exists:trainings,id',
            'form_title'       => 'nullable|string|max:200',
            'field_label'      => 'required|array',
            'field_label.*'    => 'nullable|string|max:255',
            'field_type'       => 'nullable|array',
            'field_options'    => 'nullable|array',
            'field_required'   => 'nullable|array',
        ])->validate();

        $trainerId = Auth::guard('web')->id();
        $trainingId = $data['training_id'];

        if (! Training::where('id', $trainingId)->visibleToTrainer($trainerId)->exists()) {
            return back()->with('error', 'You are not on this activity\'s team.');
        }

        $title = trim((string) $request->input('form_title', '')) ?: 'Activity Evaluation Form';

        $labels = $request->input('field_label', []);
        $types = $request->input('field_type', []);
        $optionsIn = $request->input('field_options', []);
        $requiredIn = $request->input('field_required', []);

        $fields = [];
        foreach ($labels as $i => $label) {
            if (trim((string) $label) === '') {
                continue;
            }

            $type = $types[$i] ?? 'text';

            $fields[] = [
                'label'    => trim($label),
                'type'     => $type,
                'required' => isset($requiredIn[$i]),
                'options'  => in_array($type, ['radio', 'select'], true)
                    ? array_values(array_filter(array_map('trim', explode("\n", $optionsIn[$i] ?? ''))))
                    : [],
            ];
        }

        $existing = EvalForm::where('training_id', $trainingId)->first();

        if ($existing) {
            $existing->update(['title' => $title, 'fields' => array_values($fields)]);
            $message = 'Evaluation form updated.';
        } else {
            EvalForm::create([
                'training_id' => $trainingId,
                'title'       => $title,
                'fields'      => array_values($fields),
                'created_by'  => $trainerId,
            ]);
            $message = 'Evaluation form created.';
        }

        return redirect()->route('trainer.evaluations')->with('success', $message);
    }

    /** Same notify-beneficiaries behavior as Ec\EvaluationController::sendForm() — the trainer sending it doesn't notify themselves. */
    private function sendForm(Request $request)
    {
        $trainerId = Auth::guard('web')->id();
        $trainingId = (int) $request->input('training_id');
        $training = Training::where('id', $trainingId)->visibleToTrainer($trainerId)->first();
        $form = EvalForm::where('training_id', $trainingId)->first();

        if (! $form || ! $training) {
            return redirect()->route('trainer.evaluations')->with('error', 'No form found for this activity. Create one first.');
        }

        $form->update(['sent_at' => now()]);

        $message = "An evaluation form has been sent for activity: {$training->title}";

        $beneficiaryIds = Participant::where('training_id', $trainingId)
            ->whereNotNull('beneficiary_id')
            ->distinct()
            ->pluck('beneficiary_id');

        foreach ($beneficiaryIds as $beneficiaryId) {
            Notification::create([
                'user_id'     => $beneficiaryId,
                'role'        => 'beneficiary',
                'training_id' => $trainingId,
                'message'     => $message,
                'link'        => "/beneficiary/evaluations.php?training={$trainingId}",
            ]);
        }

        return redirect()->route('trainer.evaluations')->with('success', 'Evaluation form sent. Beneficiaries have been notified.');
    }
}
