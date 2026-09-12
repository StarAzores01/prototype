<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Concerns\HandlesCoverImageUpload;
use App\Http\Controllers\Concerns\HandlesDocumentUploads;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Ec\Concerns\ValidatesTeamRoles;
use App\Models\Document;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Training;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorContract;

class TrainingController extends Controller
{
    use HandlesCoverImageUpload;
    use HandlesDocumentUploads;
    use ValidatesTeamRoles;

    public function index(Request $request)
    {
        $viewId = (int) $request->query('view', 0);

        if ($viewId) {
            $training = Training::with('program')->where('id', $viewId)->visibleToTrainer($this->trainerId())->first();
            if ($training) {
                return $this->detailView($training);
            }
            // No match (not found, or belongs to another trainer): original
            // falls through to the list view instead of a 404.
        }

        return $this->listView($request);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'create'        => $this->create($request),
            'update_budget' => $this->updateBudget($request),
            'upload_cover'  => $this->uploadCover($request),
            'upload'        => $this->uploadDocument($request),
            default         => back(),
        };
    }

    private function listView(Request $request)
    {
        $q = trim($request->query('q', ''));
        $status = $request->query('status', '');

        $query = Training::with('program')
            ->visibleToTrainer($this->trainerId())
            ->withCount('participants as trainees');

        if ($q) {
            $query->where('title', 'like', "%{$q}%");
        }
        if ($status) {
            $query->where('status', $status);
        }

        return view('trainer.trainings', [
            'activePage' => 'trainings',
            'mode'       => 'list',
            'trainings'  => $query->orderByDesc('date_start')->get(),
            'q'          => $q,
            'status'     => $status,
        ]);
    }

    private function detailView(Training $training)
    {
        $participants = Participant::with('evaluations')
            ->where('training_id', $training->id)
            ->orderBy('full_name')
            ->get();

        // Scoped by activity_id (the new FK) as well as the legacy training_id —
        // matches Ec\TrainingController::detailView() exactly; this Trainer-role
        // view was missing the activity_id half until now (see uploadDocument()
        // below, which is what actually writes activity_id).
        $documents = Document::where(function ($q) use ($training) {
            $q->where('training_id', $training->id)->orWhere('activity_id', $training->id);
        })
            ->orderByDesc('created_at')
            ->get();

        return view('trainer.trainings', [
            'activePage'       => 'trainings',
            'mode'             => 'detail',
            'viewTraining'     => $training,
            'viewParticipants' => $participants,
            'viewDocs'         => $documents,
            'progress'         => $this->progress($training),
            // Only the activity's lead can change the display picture, not just any assigned member.
            'canChangeCover'   => $training->isLeadUser($this->trainerId()),
        ]);
    }

    /**
     * Budget percentage and the derived "health" pill — same formula as the
     * EC module's training detail view. This used to also track a timeline
     * percentage; the Activity-level "Timeline Progress" monitoring it fed
     * has been removed entirely, so health is budget-only from here on.
     */
    private function progress(Training $training): array
    {
        $budgetAlloc = (float) ($training->budget_allocated ?? 0);
        $budgetUsed = (float) ($training->budget_used ?? 0);
        $budgetPct = $budgetAlloc > 0 ? min(100, round($budgetUsed / $budgetAlloc * 100, 1)) : null;

        $healthHex = 'var(--gray-300)';
        $healthLabel = 'No data';
        if ($budgetPct !== null) {
            if ($budgetPct >= 100) {
                $healthHex = '#EF4444';
                $healthLabel = 'Over Budget';
            } elseif ($budgetPct >= 80) {
                $healthHex = '#F59E0B';
                $healthLabel = 'At Risk';
            } else {
                $healthHex = '#10B981';
                $healthLabel = 'On Track';
            }
        }

        return compact('budgetAlloc', 'budgetUsed', 'budgetPct', 'healthLabel', 'healthHex');
    }

    /** Only budget_used is writable here — budget_allocated is locked for good after creation. */
    private function updateBudget(Request $request)
    {
        $data = Validator::make($request->all(), [
            'training_id' => 'required|integer|exists:trainings,id',
            'budget_used' => 'nullable|numeric|min:0|max:9999999999.99',
        ])->validate();

        $tid = (int) $data['training_id'];

        // Only allow if this training is visible to the logged-in trainer (lead or member).
        $training = Training::where('id', $tid)->visibleToTrainer($this->trainerId())->first();

        if ($training) {
            $training->update([
                'budget_used' => $data['budget_used'] ?? 0,
            ]);

            return redirect()->route('trainer.trainings', ['view' => $tid])->with('success', 'Budget usage updated successfully.');
        }

        return redirect()->route('trainer.trainings', ['view' => $tid]);
    }

    /** Restricted to this activity's team lead — a mere member can't change it. */
    private function uploadCover(Request $request)
    {
        $tid = (int) $request->input('training_id');
        $trainerId = $this->trainerId();

        $training = Training::where('id', $tid)->visibleToTrainer($trainerId)->first();

        if (! $training || ! $training->isLeadUser($trainerId)) {
            return redirect()->route('trainer.trainings', ['view' => $tid])->with('error', 'Only the activity lead can change the display picture.');
        }

        [$ok, $error] = $this->storeCoverImage($request, $training);

        if (! $ok) {
            return back()->with('error', $error);
        }

        return redirect()->route('trainer.trainings', ['view' => $tid])->with('success', 'Display picture updated.');
    }

    /**
     * A trainer can add a new Activity, but only under a Program they
     * already belong to (lead OR member — this is the collaborative case,
     * unlike Trainer\ProgramController's lead-only amendment actions).
     * program_id is validated against Program::visibleToTrainer() so a
     * tampered form can't sneak in an arbitrary program the trainer isn't
     * on. lead_id/member_ids for the Activity's own team come from the
     * full trainer pool, same as Ec\TrainingController::create() — this
     * team is independent of the Program's team.
     */
    private function create(Request $request)
    {
        $this->scrubMemberIds($request);
        $trainerId = $this->trainerId();

        $data = Validator::make($request->all(), [
            'title'               => 'required|string|max:200',
            'area'                => 'required|string|max:120',
            'description'         => 'nullable|string',
            'date_start'          => 'nullable|date',
            'date_end'            => 'nullable|date|after_or_equal:date_start',
            'budget_allocated'    => 'required|numeric|min:0|max:9999999999.99',
            'budget_used'         => 'nullable|numeric|min:0|max:9999999999.99',
            'status'              => ['nullable', Rule::in(['Proposed', 'Approved', 'Ongoing', 'Completed'])],
            'target_participants' => 'nullable|integer|min:1',
            'lead_id'             => 'required|integer|exists:users,id',
            'member_ids'          => 'nullable|array|max:3',
            'member_ids.*'        => 'integer|distinct|exists:users,id',
            'program_id'          => [
                'required',
                'integer',
                Rule::exists('programs', 'id'),
            ],
        ], [
            'member_ids.max' => 'You can assign at most 3 team members.',
        ])->after(function (ValidatorContract $validator) use ($request, $trainerId) {
            $this->validateTeamRoles($validator, (int) $request->input('lead_id'), (array) $request->input('member_ids', []));

            $programId = (int) $request->input('program_id');
            if ($programId && ! Program::visibleToTrainer($trainerId)->where('id', $programId)->exists()) {
                $validator->errors()->add('program_id', 'You can only add activities under a program you belong to.');
            }
        })->validate();

        $training = DB::transaction(function () use ($data, $trainerId) {
            $training = Training::create([
                'title'                => $data['title'],
                'area'                 => $data['area'],
                'description'          => $data['description'] ?? null,
                'date_start'           => $data['date_start'] ?? null,
                'date_end'             => $data['date_end'] ?? null,
                'status'               => $data['status'] ?? 'Proposed',
                'target_participants'  => $data['target_participants'] ?? 0,
                'budget_allocated'     => $data['budget_allocated'],
                'budget_used'          => $data['budget_used'] ?? 0,
                'created_by'           => $trainerId,
                'program_id'           => $data['program_id'],
            ]);

            $this->syncTeam($training, (int) $data['lead_id'], $data['member_ids'] ?? []);

            return $training;
        });

        return redirect()->route('trainer.trainings', ['view' => $training->id])->with('success', 'Activity created.');
    }

    /** Same wholesale-replace behavior as Ec\TrainingController::syncTeam() — kept identical on purpose. */
    private function syncTeam(Training $training, int $leadId, array $memberIds): void
    {
        $training->teamMembers()->detach();

        $training->teamMembers()->attach($leadId, ['member_role' => 'lead']);
        foreach (array_unique($memberIds) as $memberId) {
            $training->teamMembers()->attach((int) $memberId, ['member_role' => 'member']);
        }

        $training->update(['trainer_id' => $leadId]);
    }

    private function activeTrainers()
    {
        return User::where('role', 'trainer')->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
    }

    /**
     * This Activity's own document repository (activity_id, not the legacy
     * training_id) — the counterpart to Ec\TrainingController::uploadDocument()
     * that this role was missing. Lead or member may both upload, same
     * floor as viewing the activity at all.
     */
    private function uploadDocument(Request $request)
    {
        $activityId = (int) $request->input('activity_id');
        $trainerId = $this->trainerId();

        if (! Training::where('id', $activityId)->visibleToTrainer($trainerId)->exists()) {
            return back()->with('error', 'You are not on this activity\'s team.');
        }

        [$ok, $error] = $this->storeDocumentUpload($request, $trainerId, [
            'activity_id' => $activityId,
        ]);

        if (! $ok) {
            return back()->with('error', $error);
        }

        return redirect()->route('trainer.trainings', ['view' => $activityId])->with('success', 'Document added to activity.');
    }

    private function trainerId(): ?int
    {
        return Auth::guard('web')->id();
    }
}
