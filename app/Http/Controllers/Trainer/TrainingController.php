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
use App\Services\ProgramLogService;
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
            'update'        => $this->update($request),
            'delete'        => $this->delete($request),
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

        $trainings = $query->orderByRaw("CASE status WHEN 'Ongoing' THEN 0 WHEN 'Proposed' THEN 1 WHEN 'Completed' THEN 2 ELSE 3 END")
            ->orderBy('date_start')
            ->get();

        // Grouped by the linked Program ("Project") for the card grid  -  same
        // treatment as Ec\TrainingController::listView(), kept consistent
        // across roles. Unassigned activities (program_id null) group under
        // key 0.
        $trainingsByProject = $trainings->groupBy(fn ($t) => $t->program_id ?? 0);

        return view('trainer.trainings', [
            'activePage'         => 'trainings',
            'mode'               => 'list',
            'trainings'          => $trainings,
            'trainingsByProject' => $trainingsByProject,
            // Only programs this trainer is lead/member on  -  same scope
            // create() already enforces, and the same shared field set EC
            // uses (ec.partials.activity-create-fields) for its own
            // standalone Create Activity modal.
            'programs'           => Program::visibleToTrainer($this->trainerId())->orderBy('title')->get(),
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

        // Scoped by activity_id (the new FK) as well as the legacy training_id  -
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
            'budgetItems'      => $training->budgetItems()->with('loggedBy')->get(),
            // Only the activity's lead can change the display picture, not just any assigned member.
            'canChangeCover'   => $training->isLeadUser($this->trainerId()),
        ]);
    }

    /**
     * Budget percentage and the derived "health" pill  -  same formula as the
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

    /** Lead only: edit an activity's mutable fields (budget_allocated is permanently fixed at creation). */
    private function update(Request $request)
    {
        $trainerId = $this->trainerId();

        $data = Validator::make($request->all(), [
            'training_id'         => 'required|integer|exists:trainings,id',
            'title'               => 'required|string|max:200',
            'area'                => 'required|string|max:120',
            'description'         => 'nullable|string',
            'date_start'          => 'nullable|date',
            'date_end'            => 'nullable|date|after_or_equal:date_start',
            'target_participants' => 'nullable|integer|min:1',
        ])->validate();

        $training = Training::where('id', $data['training_id'])
            ->visibleToTrainer($trainerId)
            ->firstOrFail();

        // Only the lead may edit (not just any member).
        abort_unless($training->isLeadUser($trainerId), 403, 'Only this activity\'s Project Lead can edit it.');

        $training->update([
            'title'               => $data['title'],
            'area'                => $data['area'],
            'description'         => $data['description'] ?? null,
            'date_start'          => $data['date_start'] ?? null,
            'date_end'            => $data['date_end'] ?? null,
            'target_participants' => $data['target_participants'] ?? $training->target_participants,
        ]);

        return redirect()->route('trainer.trainings')->with('success', 'Activity updated.');
    }

    /** Lead only: hard-delete an activity. */
    private function delete(Request $request)
    {
        $trainerId = $this->trainerId();

        $data = Validator::make($request->all(), [
            'training_id' => 'required|integer|exists:trainings,id',
        ])->validate();

        $training = Training::where('id', $data['training_id'])
            ->visibleToTrainer($trainerId)
            ->firstOrFail();

        abort_unless($training->isLeadUser($trainerId), 403, 'Only this activity\'s Project Lead can delete it.');

        $training->delete();

        return redirect()->route('trainer.trainings')->with('success', 'Activity deleted.');
    }

    /** Only budget_used is writable here  -  budget_allocated is locked for good after creation. */
    private function updateBudget(Request $request)
    {
        $data = Validator::make($request->all(), [
            'training_id'           => 'required|integer|exists:trainings,id',
            'items'                 => 'required|array|min:1',
            'items.*.category'      => 'required|string|max:100',
            'items.*.other_specify' => 'nullable|string|max:255',
            'items.*.description'   => 'nullable|string|max:255',
            'items.*.quantity'      => 'required|numeric|min:0.01',
            'items.*.unit_cost'     => 'required|numeric|min:0',
        ])->validate();

        $tid       = (int) $data['training_id'];
        $trainerId = $this->trainerId();
        $training  = Training::where('id', $tid)->visibleToTrainer($trainerId)->first();

        if (! $training) {
            return redirect()->route('trainer.trainings', ['view' => $tid]);
        }

        DB::transaction(function () use ($data, $tid, $trainerId) {
            \App\Models\BudgetItem::where('training_id', $tid)->delete();

            $total = 0;
            foreach ($data['items'] as $item) {
                $category = $item['category'] === 'Other'
                    ? 'Other: '.trim($item['other_specify'] ?? '')
                    : $item['category'];

                $qty      = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $total   += round($qty * $unitCost, 2);

                \App\Models\BudgetItem::create([
                    'training_id' => $tid,
                    'category'    => $category,
                    'description' => $item['description'] ?? null,
                    'quantity'    => $qty,
                    'unit_cost'   => $unitCost,
                    'logged_by'   => $trainerId,
                ]);
            }

            Training::where('id', $tid)->update(['budget_used' => $total]);
        });

        $training = Training::findOrFail($tid);
        ProgramLogService::recordBudget($training, ProgramLogService::ACTION_BUDGET_LOGGED, [
            'stage'      => 'update',
            'budget_used' => (float) $training->budget_used,
            'item_count' => count($data['items']),
        ]);

        return redirect()->route('trainer.trainings', ['view' => $tid, 'budget_saved' => 1])->with('success', 'Budget breakdown saved.');
    }

    /** Restricted to this activity's team lead  -  a mere member can't change it. */
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
     * already belong to (lead OR member  -  this is the collaborative case,
     * unlike Trainer\ProgramController's lead-only amendment actions).
     * program_id is validated against Program::visibleToTrainer() so a
     * tampered form can't sneak in an arbitrary program the trainer isn't
     * on.
     *
     * FIX: this used to require lead_id (and validate budget_used as a
     * plain field), copied from an older shape of the create form. The
     * actual "Add Activity" markup both roles share
     * (resources/views/ec/partials/activity-create-fields.blade.php) has
     * had no Project Leader/Team Members inputs for a while now  -  an
     * activity inherits its team from the Program  -  and posts a
     * budget_items[] breakdown table instead of a bare budget_used field.
     * Requiring lead_id here meant every submission from that form failed
     * validation and bounced back to the (closed) modal with no visible
     * error, which looked like "the activity isn't saving." Brought in
     * line with Ec\TrainingController::create(): team is derived via
     * teamFromProgram(), and budget_used is computed from budget_items.
     */
    private function create(Request $request)
    {
        $trainerId = $this->trainerId();

        $data = Validator::make($request->all(), [
            'title'               => 'required|string|max:200',
            'area'                => 'required|string|max:120',
            'description'         => 'nullable|string',
            'date_start'          => 'nullable|date',
            'date_end'            => 'nullable|date|after_or_equal:date_start',
            'budget_allocated'    => 'nullable|numeric|min:0|max:9999999999.99',
            'target_participants' => 'nullable|integer|min:1',
            'program_id'          => [
                'required',
                'integer',
                Rule::exists('programs', 'id'),
            ],
        ])->after(function (ValidatorContract $validator) use ($request, $trainerId) {
            $programId = (int) $request->input('program_id');
            if ($programId && ! Program::visibleToTrainer($trainerId)->where('id', $programId)->exists()) {
                $validator->errors()->add('program_id', 'You can only add activities under a program you belong to.');
            }
        })->validate();

        $training = DB::transaction(function () use ($data, $request, $trainerId) {
            $budgetItems = $request->input('budget_items', []);
            $budgetUsed  = 0;
            foreach ($budgetItems as $item) {
                $budgetUsed += round((float) ($item['quantity'] ?? 0) * (float) ($item['unit_cost'] ?? 0), 2);
            }

            $training = Training::create([
                'title'                => $data['title'],
                'area'                 => $data['area'],
                'description'          => $data['description'] ?? null,
                'date_start'           => $data['date_start'] ?? null,
                'date_end'             => $data['date_end'] ?? null,
                'target_participants'  => $data['target_participants'] ?? 0,
                'budget_allocated'     => $data['budget_allocated'] ?? null,
                'budget_used'          => $budgetUsed,
                'created_by'           => $trainerId,
                'program_id'           => $data['program_id'],
            ]);

            foreach ($budgetItems as $item) {
                $cat = ($item['category'] ?? '') === 'Other'
                    ? 'Other: '.trim($item['other_specify'] ?? '')
                    : ($item['category'] ?? '');
                if (! $cat) {
                    continue;
                }
                $qty = (float) ($item['quantity'] ?? 0);
                $uc  = (float) ($item['unit_cost'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                \App\Models\BudgetItem::create([
                    'training_id' => $training->id,
                    'category'    => $cat,
                    'description' => $item['description'] ?? null,
                    'quantity'    => $qty,
                    'unit_cost'   => $uc,
                    'logged_by'   => $trainerId,
                ]);
            }

            $team = $this->teamFromProgram($data['program_id']);
            if ($team['lead_id']) {
                $this->syncTeam($training, $team['lead_id'], $team['member_ids']);
            }

            return $training;
        });

        ProgramLogService::recordBudget($training, ProgramLogService::ACTION_BUDGET_LOGGED, [
            'stage'          => 'creation',
            'budget_allocated' => (float) $training->budget_allocated,
            'budget_used'    => (float) $training->budget_used,
        ]);

        return redirect()->route('trainer.trainings', ['view' => $training->id])->with('success', 'Activity created.');
    }

    /**
     * An activity doesn't have its own Project Leader/Team Members  -  it
     * inherits whoever is assigned to its Program. Returns a null lead_id
     * (and no members) when the program has no lead yet, so create() can
     * skip syncTeam() rather than attach a nonexistent user id. Identical
     * to Ec\TrainingController::teamFromProgram()  -  kept in sync on purpose.
     *
     * @return array{lead_id: ?int, member_ids: int[]}
     */
    private function teamFromProgram(?int $programId): array
    {
        $program = $programId ? Program::with(['lead', 'members'])->find($programId) : null;

        return [
            'lead_id'    => optional($program?->lead->first())->id,
            'member_ids' => $program?->members->pluck('id')->all() ?? [],
        ];
    }

    /** Same wholesale-replace behavior as Ec\TrainingController::syncTeam()  -  kept identical on purpose. */
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
     * training_id)  -  the counterpart to Ec\TrainingController::uploadDocument()
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

        [$ok, $error, $document] = $this->storeDocumentUpload($request, $trainerId, [
            'activity_id' => $activityId,
        ]);

        if (! $ok) {
            return back()->with('error', $error);
        }

        ProgramLogService::recordDocument(
            $document,
            $document->isLink() ? ProgramLogService::ACTION_ADDED_LINK : ProgramLogService::ACTION_UPLOADED_FILE
        );

        return redirect()->route('trainer.trainings', ['view' => $activityId])->with('success', 'Document added to activity.');
    }

    private function trainerId(): ?int
    {
        return Auth::guard('web')->id();
    }
}

