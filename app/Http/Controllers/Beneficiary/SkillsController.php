<?php

namespace App\Http\Controllers\Beneficiary;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\SkillProgressEntry;
use App\Models\SkillsUtilization;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Beneficiary Skills Utilization — a repeated progress journal, not a
 * one-time survey. The old Google-Forms-style skills_forms/skills_responses
 * workflow is left entirely intact in the database (see Ec\SkillsController
 * and Trainer\SkillsController, which still read/write it); this controller
 * only replaces what the beneficiary sees at /beneficiary/skills.
 */
class SkillsController extends Controller
{
    public function index(Request $request)
    {
        $beneficiaryId = Auth::guard('beneficiary')->id();

        $entries = SkillProgressEntry::where('beneficiary_id', $beneficiaryId)
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->get();

        $myTrainings = Training::whereHas('participants', fn ($q) => $q->where('beneficiary_id', $beneficiaryId))
            ->orderByDesc('date_start')
            ->get(['id', 'title']);

        return view('beneficiary.skills', [
            'activePage'   => 'skills',
            'entries'      => $entries,
            'myTrainings'  => $myTrainings,
            'outcomeTypes' => SkillProgressEntry::OUTCOME_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'create' => $this->createEntry($request),
            'update' => $this->updateEntry($request),
            'delete' => $this->deleteEntry($request),
            default  => back(),
        };
    }

    private function rules(): array
    {
        return [
            'activity_name' => 'required|string|max:255',
            'description'   => 'nullable|string',
            'activity_date' => 'required|date|before_or_equal:today',
            'outcome_type'  => ['required', \Illuminate\Validation\Rule::in(SkillProgressEntry::OUTCOME_TYPES)],
            'service_fee'   => 'nullable|numeric|min:0|max:9999999999.99',
            'remarks'       => 'nullable|string',
            'training_id'   => 'nullable|integer|exists:trainings,id',
        ];
    }

    private function createEntry(Request $request)
    {
        $beneficiaryId = Auth::guard('beneficiary')->id();

        $data = Validator::make($request->all(), $this->rules())->validate();

        // A training_id is only honored if it's actually one of this
        // beneficiary's own trainings — never trust it blindly.
        $trainingId = $this->ownTrainingId($beneficiaryId, $data['training_id'] ?? null);

        SkillProgressEntry::create([
            'beneficiary_id' => $beneficiaryId,
            'training_id'    => $trainingId,
            'activity_name'  => $data['activity_name'],
            'description'    => $data['description'] ?? null,
            'activity_date'  => $data['activity_date'],
            'outcome_type'   => $data['outcome_type'],
            'service_fee'    => $data['service_fee'] ?? 0,
            'remarks'        => $data['remarks'] ?? null,
        ]);

        // Keep this training's skills_utilization row (the EC/Trainer
        // dashboard's "Skills Utilization" percentages) in sync — see
        // SkillsUtilization::recomputeForTraining().
        SkillsUtilization::recomputeForTraining($trainingId);

        return redirect()->route('beneficiary.skills')->with('success', 'Progress entry added.');
    }

    private function updateEntry(Request $request)
    {
        $beneficiaryId = Auth::guard('beneficiary')->id();

        $data = Validator::make($request->all(), array_merge($this->rules(), [
            'entry_id' => 'required|integer',
        ]))->validate();

        // Ownership check: only ever load a row that belongs to THIS
        // beneficiary — the id in the request never bypasses that.
        $entry = SkillProgressEntry::where('id', $data['entry_id'])
            ->where('beneficiary_id', $beneficiaryId)
            ->first();

        if (! $entry) {
            return redirect()->route('beneficiary.skills')->with('error', 'Entry not found.');
        }

        $trainingId = $this->ownTrainingId($beneficiaryId, $data['training_id'] ?? null);
        $previousTrainingId = $entry->training_id;

        $entry->update([
            'training_id'   => $trainingId,
            'activity_name' => $data['activity_name'],
            'description'   => $data['description'] ?? null,
            'activity_date' => $data['activity_date'],
            'outcome_type'  => $data['outcome_type'],
            'service_fee'   => $data['service_fee'] ?? 0,
            'remarks'       => $data['remarks'] ?? null,
        ]);

        // Recompute both trainings when the entry moved from one to
        // another — the old one loses this entry's contribution, the new
        // one gains it. See SkillsUtilization::recomputeForTraining().
        SkillsUtilization::recomputeForTraining($previousTrainingId);
        if ($trainingId !== $previousTrainingId) {
            SkillsUtilization::recomputeForTraining($trainingId);
        }

        return redirect()->route('beneficiary.skills')->with('success', 'Progress entry updated.');
    }

    private function deleteEntry(Request $request)
    {
        $beneficiaryId = Auth::guard('beneficiary')->id();

        $entry = SkillProgressEntry::where('id', (int) $request->input('entry_id'))
            ->where('beneficiary_id', $beneficiaryId)
            ->first();

        if ($entry) {
            $trainingId = $entry->training_id;
            $entry->delete();
            SkillsUtilization::recomputeForTraining($trainingId);
        }

        return redirect()->route('beneficiary.skills')->with('success', 'Progress entry deleted.');
    }

    /** Only accept a training_id that's actually one of this beneficiary's own. */
    private function ownTrainingId(int $beneficiaryId, ?int $trainingId): ?int
    {
        if (! $trainingId) {
            return null;
        }

        $owns = Participant::where('training_id', $trainingId)
            ->where('beneficiary_id', $beneficiaryId)
            ->exists();

        return $owns ? $trainingId : null;
    }
}
