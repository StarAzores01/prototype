<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\EvalForm;
use App\Models\EvalResponse;
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
        $viewTrainingId = (int) $request->query('responses', 0);
        $editTrainingId = (int) $request->query('edit_form', 0);

        if ($viewTrainingId) {
            $form = EvalForm::with('training')->where('training_id', $viewTrainingId)->first();
            if ($form) {
                return $this->responsesView($form);
            }
        }

        if ($editTrainingId) {
            $training = Training::find($editTrainingId);
            if ($training) {
                return $this->formBuilderView($training);
            }
        }

        return $this->trainingsTableView();
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

    private function trainingsTableView()
    {
        $trainings = Training::query()
            ->withCount('participants as total_pax')
            ->withCount(['evaluations as submitted_count' => fn ($q) => $q->where('status', 'Submitted')])
            ->withCount(['evaluations as pending_count' => fn ($q) => $q->where('status', 'Pending')])
            ->with(['evalForms' => fn ($q) => $q->withCount('responses')])
            ->orderByDesc('date_start')
            ->get();

        return view('ec.evaluations', [
            'activePage' => 'evaluations',
            'mode'       => 'list',
            'trainings'  => $trainings,
        ]);
    }

    private function responsesView(EvalForm $form)
    {
        $responses = EvalResponse::with('beneficiary')
            ->where('form_id', $form->id)
            ->orderByDesc('submitted_at')
            ->get();

        return view('ec.evaluations', [
            'activePage'   => 'evaluations',
            'mode'         => 'responses',
            'viewForm'     => $form,
            'viewTraining' => $form->training,
            'responses'    => $responses,
        ]);
    }

    private function formBuilderView(Training $training)
    {
        $form = EvalForm::where('training_id', $training->id)->first();

        return view('ec.evaluations', [
            'activePage'   => 'evaluations',
            'mode'         => 'builder',
            'editTraining' => $training,
            'editForm'     => $form,
        ]);
    }

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

        $trainingId = $data['training_id'];
        $title = trim((string) $request->input('form_title', '')) ?: 'Training Evaluation Form';

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
                'created_by'  => Auth::guard('web')->id(),
            ]);
            $message = 'Evaluation form created.';
        }

        return redirect()->route('ec.evaluations')->with('success', $message);
    }

    private function sendForm(Request $request)
    {
        $trainingId = (int) $request->input('training_id');
        $training = Training::find($trainingId);
        $form = EvalForm::where('training_id', $trainingId)->first();

        if (! $form || ! $training) {
            return redirect()->route('ec.evaluations')->with('error', 'No form found for this training. Create one first.');
        }

        $form->update(['sent_at' => now()]);

        $message = "An evaluation form has been sent for training: {$training->title}";

        if ($training->trainer_id) {
            Notification::create([
                'user_id'     => $training->trainer_id,
                'role'        => 'trainer',
                'training_id' => $trainingId,
                'message'     => $message,
                'link'        => "/trainer/evaluations.php?training={$trainingId}",
            ]);
        }

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

        return redirect()->route('ec.evaluations')->with('success', 'Evaluation form sent. Project Leader and beneficiaries have been notified.');
    }
}
