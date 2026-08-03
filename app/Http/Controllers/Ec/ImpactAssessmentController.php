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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
                Storage::disk('public')->delete('uploads/'.$assessment->file_name);
            }
            $assessment->delete();
        }

        return redirect()->route('ec.impact_assessment')->with('success', 'Assessment deleted.');
    }

    private function createForm(Request $request)
    {
        $data = Validator::make($request->all(), [
            'training_id'    => 'required|integer|exists:trainings,id',
            'form_title'     => 'nullable|string|max:200',
            'field_label'    => 'nullable|array',
            'field_type'     => 'nullable|array',
            'field_required' => 'nullable|array',
            'field_options'  => 'nullable|array',
        ])->validate();

        $labels = $request->input('field_label', []);
        $types = $request->input('field_type', []);
        $requiredIn = $request->input('field_required', []);
        $optionsIn = $request->input('field_options', []);

        $fields = [];
        foreach ($labels as $i => $label) {
            if (trim((string) $label) === '') {
                continue;
            }

            $type = $types[$i] ?? 'text';
            $field = [
                'label'    => trim($label),
                'type'     => $type,
                'required' => isset($requiredIn[$i]),
            ];

            if (in_array($type, ['radio', 'select'], true)) {
                $field['options'] = array_values(array_filter(array_map('trim', explode("\n", $optionsIn[$i] ?? ''))));
            }

            $fields[] = $field;
        }

        if (empty($fields)) {
            return redirect()->route('ec.impact_assessment', ['tab' => 'surveys'])->with('error', 'Please add at least one question.');
        }

        $form = ImpactAssessmentForm::create([
            'training_id' => $data['training_id'],
            'title'       => trim((string) $request->input('form_title', '')) ?: 'Impact Assessment Survey',
            'fields'      => $fields,
            'created_by'  => Auth::guard('web')->id(),
            'sent_at'     => now(),
        ]);

        $beneficiaryIds = Participant::where('training_id', $form->training_id)
            ->whereNotNull('beneficiary_id')
            ->pluck('beneficiary_id');

        foreach ($beneficiaryIds as $beneficiaryId) {
            Notification::create([
                'user_id'     => $beneficiaryId,
                'role'        => 'beneficiary',
                'training_id' => $form->training_id,
                'message'     => 'A new Impact Assessment survey has been sent to you.',
                'link'        => '/beneficiary/impact_assessment.php',
            ]);
        }

        return redirect()->route('ec.impact_assessment', ['tab' => 'surveys'])->with('success', 'Impact assessment form created and sent to participants.');
    }

    private function deleteForm(Request $request)
    {
        ImpactAssessmentForm::where('id', (int) $request->input('form_id'))->delete();

        return redirect()->route('ec.impact_assessment', ['tab' => 'surveys'])->with('success', 'Survey form deleted.');
    }
}
