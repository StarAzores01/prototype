<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Participant;
use App\Models\SkillsForm;
use App\Models\SkillsResponse;
use App\Models\SkillsUtilization;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SkillsController extends Controller
{
    public function index(Request $request)
    {
        $trainerId = Auth::guard('web')->id();

        $viewFormId = (int) $request->query('view_responses', 0);
        $editTrainingId = (int) $request->query('edit_form', 0);

        if ($viewFormId) {
            $form = SkillsForm::where('id', $viewFormId)
                ->whereHas('training', fn ($t) => $t->visibleToTrainer($trainerId))
                ->with('training')
                ->first();

            if ($form) {
                $form->total_pax = Participant::where('training_id', $form->training_id)->count();

                return $this->responsesView($form);
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
        $skills = SkillsUtilization::whereHas('training', fn ($t) => $t->visibleToTrainer($trainerId))
            ->selectRaw('AVG(personal_use_pct) as personal, AVG(income_gen_pct) as income, AVG(employment_pct) as employment')
            ->first();

        $overview = [
            'personal'   => round((float) ($skills->personal ?? 0), 1),
            'income'     => round((float) ($skills->income ?? 0), 1),
            'employment' => round((float) ($skills->employment ?? 0), 1),
        ];

        $trainingSummary = Training::visibleToTrainer($trainerId)
            ->withCount('participants as total_pax')
            ->with(['skillsForms' => fn ($q) => $q->withCount('responses')])
            ->orderByDesc('date_start')
            ->get()
            ->map(function ($t) {
                $form = $t->skillsForms->first();
                $t->form = $form;
                $t->answered = $form->responses_count ?? 0;

                return $t;
            });

        return view('trainer.skills', [
            'activePage'       => 'skills',
            'mode'             => 'list',
            'overview'         => $overview,
            'trainingSummary'  => $trainingSummary,
        ]);
    }

    private function formBuilderView(Training $training)
    {
        $form = SkillsForm::where('training_id', $training->id)->first();

        return view('trainer.skills', [
            'activePage'   => 'skills',
            'mode'         => 'builder',
            'editTraining' => $training,
            'editForm'     => $form,
        ]);
    }

    private function responsesView(SkillsForm $form)
    {
        $responses = SkillsResponse::with('beneficiary')
            ->where('form_id', $form->id)
            ->orderByDesc('submitted_at')
            ->get();

        return view('trainer.skills', [
            'activePage'   => 'skills',
            'mode'         => 'responses',
            'viewForm'     => $form,
            'viewTraining' => $form->training,
            'responses'    => $responses,
        ]);
    }

    private function saveForm(Request $request)
    {
        $trainerId = Auth::guard('web')->id();

        $data = Validator::make($request->all(), [
            'training_id'    => 'required|integer|exists:trainings,id',
            'form_title'     => 'nullable|string|max:200',
            'field_label'    => 'nullable|array',
            'field_label.*'  => 'nullable|string|max:255',
            'field_type'     => 'nullable|array',
            'field_options'  => 'nullable|array',
            'field_required' => 'nullable|array',
        ])->validate();

        $trainingId = $data['training_id'];

        $training = Training::where('id', $trainingId)->visibleToTrainer($trainerId)->first();
        if (! $training) {
            return redirect()->route('trainer.skills')->with('error', 'Unauthorized.');
        }

        $title = trim((string) $request->input('form_title', '')) ?: 'Skills Utilization Survey';

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

        $existing = SkillsForm::where('training_id', $trainingId)->first();

        if ($existing) {
            $existing->update(['title' => $title, 'fields' => array_values($fields)]);
            $message = 'Skills form updated.';
        } else {
            SkillsForm::create([
                'training_id' => $trainingId,
                'title'       => $title,
                'fields'      => array_values($fields),
                'created_by'  => $trainerId,
            ]);
            $message = 'Skills form created.';
        }

        return redirect()->route('trainer.skills')->with('success', $message);
    }

    private function sendForm(Request $request)
    {
        $trainerId = Auth::guard('web')->id();
        $trainingId = (int) $request->input('training_id');

        $training = Training::where('id', $trainingId)->visibleToTrainer($trainerId)->first();
        if (! $training) {
            return redirect()->route('trainer.skills')->with('error', 'Unauthorized.');
        }

        $form = SkillsForm::where('training_id', $trainingId)->first();

        if (! $form) {
            return redirect()->route('trainer.skills')->with('error', 'No form found. Create one first.');
        }

        $form->update(['sent_at' => now()]);

        $message = 'A Skills Utilization survey has been sent for training: '.$training->title;
        $link = "/beneficiary/skills.php?training={$trainingId}";

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
                'link'        => $link,
            ]);
        }

        return redirect()->route('trainer.skills')->with('success', 'Skills form sent to beneficiaries.');
    }
}
