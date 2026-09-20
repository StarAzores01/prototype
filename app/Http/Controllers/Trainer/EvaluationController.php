<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\EvalForm;
use App\Models\EvalResponse;
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
        $viewFormId     = (int) $request->query('rform', 0);
        $editTrainingId = (int) $request->query('edit_form', 0);

        if ($viewTrainingId) {
            $training = Training::where('id', $viewTrainingId)->visibleToTrainer($trainerId)->first();

            if ($training) {
                // A training can have several forms now (one per send_date) —
                // ?rform= picks a specific one; without it, fall back to the
                // first form so old links still work.
                $form = $viewFormId
                    ? EvalForm::where('id', $viewFormId)->where('training_id', $viewTrainingId)->first()
                    : EvalForm::where('training_id', $viewTrainingId)->first();

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
            'save_form'   => $this->saveForm($request),
            'delete_form' => $this->deleteForm($request),
            default       => back(),
        };
    }

    private function mainView(int $trainerId)
    {
        $evalData = Training::visibleToTrainer($trainerId)
            ->withCount('participants as total_pax')
            ->with(['evalForms' => fn ($q) => $q->withCount('responses')->orderBy('send_date')])
            ->orderByRaw("CASE status WHEN 'Ongoing' THEN 0 WHEN 'Proposed' THEN 1 WHEN 'Completed' THEN 2 ELSE 3 END")
            ->orderBy('date_start')
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

    private function formBuilderView(Training $training)
    {
        $formId = request()->query('form_id', 0);
        $form   = $formId
            ? EvalForm::where('id', $formId)->where('training_id', $training->id)->first()
            : null;

        // A form that has already been sent is locked — its questions must
        // stay exactly as beneficiaries saw them, so editing is blocked
        // both here (server-side) and by hiding the Edit button in the view.
        if ($form && $form->sent_at !== null) {
            return redirect()->route('trainer.evaluations')
                ->with('error', 'This evaluation form has already been sent and can no longer be edited.');
        }

        $usedDates = EvalForm::where('training_id', $training->id)
            ->when($form, fn ($q) => $q->where('id', '!=', $form->id))
            ->whereNotNull('send_date')
            ->pluck('send_date')
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->values();

        return view('trainer.evaluations', [
            'activePage'   => 'evaluations',
            'mode'         => 'builder',
            'editTraining' => $training,
            'editForm'     => $form,
            'usedDates'    => $usedDates,
        ]);
    }

    /** Identical field-building logic to Ec\EvaluationController::saveForm(), gated to this trainer's own activities. */
    private function saveForm(Request $request)
    {
        $data = Validator::make($request->all(), [
            'training_id'      => 'required|integer|exists:trainings,id',
            'form_id'          => 'nullable|integer|exists:eval_forms,id',
            'form_title'       => 'nullable|string|max:200',
            'send_date'        => 'required|date',
            'field_label'      => 'required|array',
            'field_label.*'    => 'nullable|string|max:255',
            'field_type'       => 'nullable|array',
            'field_options'    => 'nullable|array',
            'field_required'   => 'nullable|array',
        ])->after(function ($validator) use ($request) {
            $trainerId  = $this->trainerId();
            $trainingId = (int) $request->input('training_id');

            if (! Training::where('id', $trainingId)->visibleToTrainer($trainerId)->exists()) {
                $validator->errors()->add('training_id', 'You are not on this activity\'s team.');
                return;
            }

            $training  = Training::find($trainingId);
            $sendDate  = $request->input('send_date');
            $dateStart = $training->date_start?->format('Y-m-d');
            $dateEnd   = $training->date_end?->format('Y-m-d');
            $today     = now()->format('Y-m-d');

            if ($dateStart && $sendDate < $dateStart) {
                $validator->errors()->add('send_date', 'Send date cannot be before the activity start date ('.$dateStart.').');
            }
            if ($dateEnd && $sendDate > $dateEnd) {
                $validator->errors()->add('send_date', 'Send date cannot be after the activity end date ('.$dateEnd.').');
            }
            if ($sendDate < $today) {
                $validator->errors()->add('send_date', 'Send date cannot be in the past.');
            }

            $formId    = (int) $request->input('form_id', 0);
            $duplicate = EvalForm::where('training_id', $trainingId)
                ->where('send_date', $sendDate)
                ->when($formId, fn ($q) => $q->where('id', '!=', $formId))
                ->exists();
            if ($duplicate) {
                $validator->errors()->add('send_date', 'A form is already scheduled for this date on the same activity.');
            }
        })->validate();

        $trainerId  = $this->trainerId();
        $trainingId = $data['training_id'];
        $formId     = (int) ($data['form_id'] ?? 0);
        $title      = trim((string) $request->input('form_title', '')) ?: 'Activity Evaluation Form';

        $labels     = $request->input('field_label', []);
        $types      = $request->input('field_type', []);
        $optionsIn  = $request->input('field_options', []);
        $requiredIn = $request->input('field_required', []);

        $fields = [];
        foreach ($labels as $i => $label) {
            if (trim((string) $label) === '') continue;
            $type     = $types[$i] ?? 'text';
            $fields[] = [
                'label'    => trim($label),
                'type'     => $type,
                'required' => isset($requiredIn[$i]),
                'options'  => in_array($type, ['radio', 'select'], true)
                    ? array_values(array_filter(array_map('trim', explode("\n", $optionsIn[$i] ?? ''))))
                    : [],
            ];
        }

        if ($formId) {
            $existing  = EvalForm::where('id', $formId)->where('training_id', $trainingId)->firstOrFail();

            // Locked once sent — see formBuilderView() for the matching
            // server-side guard that keeps someone from even reaching this
            // form's edit page in the first place.
            if ($existing->sent_at !== null) {
                return redirect()->route('trainer.evaluations')
                    ->with('error', 'This evaluation form has already been sent and can no longer be edited.');
            }

            $resetSent = $existing->send_date?->format('Y-m-d') !== $data['send_date'];
            $existing->update([
                'title'     => $title,
                'fields'    => array_values($fields),
                'send_date' => $data['send_date'],
                'sent_at'   => $resetSent ? null : $existing->sent_at,
            ]);
            $message = 'Evaluation form updated.';
        } else {
            EvalForm::create([
                'training_id' => $trainingId,
                'title'       => $title,
                'fields'      => array_values($fields),
                'send_date'   => $data['send_date'],
                'created_by'  => $trainerId,
            ]);
            $message = 'Evaluation form scheduled.';
        }

        return redirect()->route('trainer.evaluations')->with('success', $message);
    }

    private function deleteForm(Request $request)
    {
        $trainerId = $this->trainerId();

        $data = Validator::make($request->all(), [
            'form_id'     => 'required|integer|exists:eval_forms,id',
            'training_id' => 'required|integer|exists:trainings,id',
        ])->validate();

        // Only allow deletion if the trainer is on the activity's team
        if (! Training::where('id', $data['training_id'])->visibleToTrainer($trainerId)->exists()) {
            return back()->with('error', 'You are not on this activity\'s team.');
        }

        EvalForm::where('id', $data['form_id'])
            ->where('training_id', $data['training_id'])
            ->delete();

        return redirect()->route('trainer.evaluations')->with('success', 'Evaluation form deleted.');
    }
}
