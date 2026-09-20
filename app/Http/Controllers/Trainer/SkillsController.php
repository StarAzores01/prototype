<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Participant;
use App\Models\SkillProgressEntry;
use App\Models\SkillsForm;
use App\Models\SkillsResponse;
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
        // Read-only monitoring of this trainer's own beneficiaries' progress
        // journal — same source SkillsUtilization::recomputeForTraining()
        // derives its percentages from, scoped to trainings this trainer can
        // see (see Ec\SkillsController for the EC-wide, unscoped version).
        $progressEntries = SkillProgressEntry::with(['beneficiary:id,first_name,last_name', 'training:id,title'])
            ->whereHas('training', fn ($t) => $t->visibleToTrainer($trainerId))
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->get();

        $totalRecordedEarnings = $progressEntries->sum('service_fee');

        // Grouped by activity (training) — see Ec\SkillsController for the
        // same grouping on the EC-wide version of this table.
        $entriesByActivity = $progressEntries->groupBy(
            fn ($e) => $e->training->title ?? $e->activity_name
        );

        return view('trainer.skills', [
            'activePage'            => 'skills',
            'mode'                  => 'list',
            'progressEntries'       => $progressEntries,
            'entriesByActivity'     => $entriesByActivity,
            'totalRecordedEarnings' => $totalRecordedEarnings,
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
        $link = route('beneficiary.skills', ['training' => $trainingId]);

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
