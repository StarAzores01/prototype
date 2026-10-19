<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\ImpactAssessment;
use App\Models\ImpactAssessmentForm;
use App\Models\ImpactAssessmentResponse;
use App\Models\Notification;
use App\Models\Participant;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ImpactAssessmentController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'evaluators');

        return $tab === 'surveys'
            ? $this->surveysTab($request)
            : $this->evaluatorsTab($request);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'review'      => $this->review($request),
            'delete'      => $this->delete($request),
            'create_form' => $this->createForm($request),
            'delete_form' => $this->deleteForm($request),
            default       => back(),
        };
    }

    private function evaluatorsTab(Request $request)
    {
        $q = trim($request->query('q', ''));
        $filterStatus = trim($request->query('status', ''));
        $oldAssessmentId = $request->old('assessment_id');
        $creationAssessment = is_scalar($oldAssessmentId) && ctype_digit((string) $oldAssessmentId)
            ? ImpactAssessment::find($oldAssessmentId)
            : null;

        $assessmentsQuery = ImpactAssessment::with(['training', 'evaluator']);

        if ($q) {
            $assessmentsQuery->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhereHas('evaluator', function ($evaluatorQuery) use ($q) {
                        $evaluatorQuery->where('first_name', 'like', "%{$q}%")
                            ->orWhere('last_name', 'like', "%{$q}%");
                    });
            });
        }
        if ($filterStatus) {
            $assessmentsQuery->where('status', $filterStatus);
        }

        return view('ec.impact_assessment', [
            'activePage'   => 'impact_assessment',
            'tab'          => 'evaluators',
            'assessments'  => $assessmentsQuery->orderByDesc('submitted_at')->get(),
            'q'            => $q,
            'filterStatus' => $filterStatus,
            'trainings'    => Training::orderBy('title')->get(['id', 'title']),
            'creationAssessment' => $creationAssessment,
        ]);
    }

    private function surveysTab(Request $request)
    {
        $viewFormId = (int) $request->query('view_form', 0);

        if ($viewFormId) {
            $form = ImpactAssessmentForm::with('training')->find($viewFormId);

            if ($form) {
                $responses = ImpactAssessmentResponse::with('beneficiary')
                    ->where('form_id', $viewFormId)
                    ->orderByDesc('submitted_at')
                    ->get();

                return view('ec.impact_assessment', [
                    'activePage' => 'impact_assessment',
                    'tab'        => 'surveys',
                    'viewForm'   => $form,
                    'responses'  => $responses,
                ]);
            }
            // No matching form: original silently falls back to the list — same here.
        }

        $surveyForms = ImpactAssessmentForm::with('training')
            ->withCount('responses')
            ->orderByDesc('sent_at')
            ->get();

        $this->attachParticipantTotals($surveyForms);

        return view('ec.impact_assessment', [
            'activePage'  => 'impact_assessment',
            'tab'         => 'surveys',
            'surveyForms' => $surveyForms,
            'trainings'   => Training::orderBy('title')->get(['id', 'title']),
            'viewForm'    => null,
        ]);
    }

    /**
     * Total distinct beneficiaries enrolled per training, attached to each
     * form as total_participants (matching the original's per-row subquery).
     */
    private function attachParticipantTotals($surveyForms): void
    {
        $trainingIds = $surveyForms->pluck('training_id')->unique();

        $totals = Participant::whereIn('training_id', $trainingIds)
            ->whereNotNull('beneficiary_id')
            ->selectRaw('training_id, count(distinct beneficiary_id) as cnt')
            ->groupBy('training_id')
            ->pluck('cnt', 'training_id');

        foreach ($surveyForms as $form) {
            $form->total_participants = (int) ($totals[$form->training_id] ?? 0);
        }
    }

    private function review(Request $request)
    {
        $data = Validator::make($request->all(), [
            'assessment_id' => 'required|integer|exists:impact_assessments,id',
            'ec_notes'      => 'nullable|string',
        ])->validate();

        ImpactAssessment::where('id', $data['assessment_id'])->update([
            'status'      => 'Reviewed',
            'ec_notes'    => trim($request->input('ec_notes', '')),
            'reviewed_at' => now(),
        ]);

        return redirect()->route('ec.impact_assessment')->with('success', 'Assessment marked as reviewed.');
    }

    private function delete(Request $request)
    {
        $assessment = ImpactAssessment::find((int) $request->input('assessment_id'));

        if ($assessment) {
            if ($assessment->file_name) {
                Storage::disk('local')->delete('uploads/'.$assessment->file_name);
            }
            $assessment->delete();
        }

        return redirect()->route('ec.impact_assessment')->with('success', 'Assessment deleted.');
    }

    private function createForm(Request $request)
    {
        $data = Validator::make($request->all(), [
            'training_id' => [Rule::requiredIf(! $request->filled('assessment_id')), 'nullable', 'integer', 'exists:trainings,id'],
            'assessment_id' => 'nullable|integer|exists:impact_assessments,id',
            'due_date' => 'nullable|date',
            'form_title' => 'nullable|string|max:200',
            'field_label' => 'nullable|array',
            'field_label.*' => 'required|string|max:255',
            'field_type' => 'nullable|array',
            'field_type.*' => 'required|string|in:text,textarea,radio,select,rating',
            'field_required' => 'nullable|array',
            'field_required.*' => 'boolean',
            'field_options' => 'nullable|array',
            'field_options.*' => 'nullable|string|max:5000',
        ])->validate();

        return DB::transaction(function () use ($data) {
            // Every sender locks this row before checking or creating its link.
            // The form, link and notifications commit or roll back together.
            $assessment = ! empty($data['assessment_id'])
                ? ImpactAssessment::whereKey($data['assessment_id'])->lockForUpdate()->first()
                : null;
            if (! empty($data['assessment_id']) && ! $assessment) {
                throw ValidationException::withMessages(['assessment_id' => 'This assessment is no longer available.']);
            }
            if ($assessment) {
                if ($assessment->participant_form_id !== null) {
                    throw ValidationException::withMessages(['assessment_id' => 'This assessment already has a linked participant form.']);
                }
                if (! in_array($assessment->status, ['Submitted', 'Reviewed'], true)) {
                    throw ValidationException::withMessages(['assessment_id' => 'Only submitted or reviewed assessments can be sent.']);
                }
                if (! $assessment->training_id || ! $assessment->training()->exists()) {
                    throw ValidationException::withMessages(['assessment_id' => 'This assessment has no linked activity.']);
                }
                if (isset($data['training_id']) && (int) $data['training_id'] !== (int) $assessment->training_id) {
                    throw ValidationException::withMessages(['training_id' => 'The activity must match the linked assessment.']);
                }
                if ($assessment->form_data === null && (! $assessment->file_name
                    || ! Storage::disk('local')->exists('uploads/'.$assessment->file_name))) {
                    throw ValidationException::withMessages(['assessment_id' => 'The uploaded assessment file is unavailable. Please restore the file before creating its participant form.']);
                }
                $data['training_id'] = $assessment->training_id;
                $data['due_date'] = $assessment->due_date?->format('Y-m-d') ?? ($data['due_date'] ?? null);
            }

            if ($assessment && $assessment->form_data !== null) {
                // Built forms use the evaluator's definition, never posted EC replacements.
                $fields = $this->assessmentFields($assessment->form_data);
            } else {
                $fields = [];
                foreach ($data['field_label'] ?? [] as $i => $label) {
                    $type = $data['field_type'][$i] ?? 'text';
                    $field = [
                        'label' => trim($label), 'type' => $type,
                        'required' => (string) ($data['field_required'][$i] ?? '0') === '1',
                    ];
                    if ($type === 'rating') {
                        $field['type'] = 'radio';
                        $field['options'] = $this->ratingScale();
                    } elseif (in_array($type, ['radio', 'select'], true)) {
                        $field['options'] = array_values(array_filter(
                            array_map('trim', explode("\n", $data['field_options'][$i] ?? '')),
                            fn ($option) => $option !== ''
                        ));
                        if ($field['options'] === []) {
                            throw ValidationException::withMessages(['field_options.'.$i => 'Please add at least one choice for this question.']);
                        }
                    }
                    $fields[] = $field;
                }
                if ($fields === []) {
                    throw ValidationException::withMessages(['field_label' => 'Please add at least one question.']);
                }
            }

            $form = ImpactAssessmentForm::create([
                'training_id' => $data['training_id'],
                'due_date' => $data['due_date'] ?? null,
                'title' => $assessment && $assessment->form_data !== null
                    ? $assessment->title
                    : (trim((string) ($data['form_title'] ?? '')) ?: 'Impact Assessment Survey'),
                'fields' => $fields,
                'created_by' => Auth::guard('web')->id(),
                'sent_at' => now(),
            ]);
            if ($assessment) {
                $assessment->update(['participant_form_id' => $form->id]);
            }

            $beneficiaryIds = Participant::where('training_id', $form->training_id)
                ->whereNotNull('beneficiary_id')->distinct()->pluck('beneficiary_id');
            foreach ($beneficiaryIds as $beneficiaryId) {
                Notification::create([
                    'user_id' => $beneficiaryId, 'role' => 'beneficiary',
                    'training_id' => $form->training_id,
                    'message' => 'A new Impact Assessment survey has been sent to you.',
                    'link' => route('beneficiary.impact_assessment'),
                ]);
            }

            return redirect()->route('ec.impact_assessment', ['tab' => 'surveys'])
                ->with('success', 'Impact assessment form created and sent to participants.');
        });
    }

    private function ratingScale(): array
    {
        return ['5 - Excellent', '4 - Very Good', '3 - Good', '2 - Fair', '1 - Poor'];
    }

    private function assessmentFields(mixed $questions): array
    {
        if (! is_array($questions) || ! array_is_list($questions) || $questions === []) {
            throw ValidationException::withMessages(['assessment_id' => 'The evaluator form has no valid questions.']);
        }
        $fields = [];
        foreach ($questions as $question) {
            if (! is_array($question) || ! is_string($question['label'] ?? null)
                || trim($question['label']) === ''
                || ! in_array($question['type'] ?? null, ['text', 'textarea', 'radio', 'select', 'rating'], true)
                || (isset($question['required']) && ! is_bool($question['required']))) {
                throw ValidationException::withMessages(['assessment_id' => 'The evaluator form has an invalid question.']);
            }
            $field = [
                'label' => $question['label'], 'type' => $question['type'],
                'required' => $question['required'] ?? false,
            ];
            if ($field['type'] === 'rating') {
                $field['type'] = 'radio';
                $field['options'] = $this->ratingScale();
            } elseif (in_array($field['type'], ['radio', 'select'], true)) {
                $options = $question['options'] ?? null;
                if (! is_array($options) || ! array_is_list($options) || $options === []
                    || count(array_filter($options, fn ($value) => is_string($value) && trim($value) !== '')) !== count($options)) {
                    throw ValidationException::withMessages(['assessment_id' => 'The evaluator form has invalid question choices.']);
                }
                $field['options'] = $options;
            }
            $fields[] = $field;
        }

        return $fields;
    }

    private function deleteForm(Request $request)
    {
        ImpactAssessmentForm::where('id', (int) $request->input('form_id'))->delete();

        return redirect()->route('ec.impact_assessment', ['tab' => 'surveys'])->with('success', 'Survey form deleted.');
    }
}
