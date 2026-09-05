<?php

namespace App\Http\Controllers\Ec;

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
    use ValidatesTeamRoles;
    use HandlesDocumentUploads;
    use HandlesCoverImageUpload;

    public function index(Request $request)
    {
        $viewId = (int) $request->query('view', 0);

        if ($viewId) {
            $training = Training::with(['trainer', 'program', 'lead', 'members'])->find($viewId);
            if ($training) {
                return $this->detailView($training);
            }
            // No match: original falls through to the list view instead of a 404.
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
            'update_status' => $this->updateStatus($request),
            'update_budget' => $this->updateBudget($request),
            'upload'        => $this->uploadDocument($request),
            'upload_cover'  => $this->uploadCover($request),
            default         => back(),
        };
    }

    private function listView(Request $request)
    {
        $q = trim($request->query('q', ''));
        $status = $request->query('status', '');
        $area = $request->query('area', '');

        $query = Training::with(['trainer', 'program'])->withCount('participants as enrolled');

        if ($q) {
            $query->where(function ($queryBuilder) use ($q) {
                $queryBuilder->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }
        if ($area) {
            $query->where('area', $area);
        }
        // "Upcoming" is a display-only merge of Proposed+Approved — the
        // original filtered the raw status column against this label
        // directly, which could never match anything. Map it properly.
        if ($status === 'Upcoming') {
            $query->whereIn('status', ['Proposed', 'Approved']);
        } elseif (in_array($status, ['Ongoing', 'Completed'], true)) {
            $query->where('status', $status);
        }

        return view('ec.trainings', [
            'activePage' => 'trainings',
            'mode'       => 'list',
            'trainings'  => $query->orderByDesc('date_start')->get(),
            'areas'      => Training::whereNotNull('area')->distinct()->orderBy('area')->pluck('area'),
            'trainers'   => $this->activeTrainers(),
            'programs'   => $this->allPrograms(),
            'q'          => $q,
            'status'     => $status,
            'area'       => $area,
        ]);
    }

    private function detailView(Training $training)
    {
        $participants = Participant::with('evaluations')
            ->where('training_id', $training->id)
            ->orderBy('full_name')
            ->get();

        // Scoped by activity_id (the new FK) as well as the legacy training_id,
        // so documents already attached the old way keep showing up here while
        // the upload feature (Phase D) is what will actually start writing
        // activity_id going forward.
        $documents = Document::with('uploader')
            ->where(function ($q) use ($training) {
                $q->where('training_id', $training->id)->orWhere('activity_id', $training->id);
            })
            ->orderByDesc('created_at')
            ->get();

        return view('ec.trainings', [
            'activePage'       => 'trainings',
            'mode'             => 'detail',
            'viewTraining'     => $training,
            'viewParticipants' => $participants,
            'viewDocs'         => $documents,
            'trainers'         => $this->activeTrainers(),
            'programs'         => $this->allPrograms(),
            'progress'         => $this->progress($training),
        ]);
    }

    /**
     * Budget/timeline percentages and the derived "health" pill shown on
     * the detail view's Project Progress card.
     */
    private function progress(Training $training): array
    {
        $budgetAlloc = (float) ($training->budget_allocated ?? 0);
        $budgetUsed = (float) ($training->budget_used ?? 0);
        $budgetPct = $budgetAlloc > 0 ? min(100, round($budgetUsed / $budgetAlloc * 100, 1)) : null;
        $budgetRemain = $budgetAlloc > 0 ? $budgetAlloc - $budgetUsed : null;

        $dateStart = $training->date_start;
        $dateEnd = $training->date_end;
        $today = now()->startOfDay();
        $timePct = null;
        $daysLeft = null;
        $totalDays = null;

        if ($dateStart && $dateEnd) {
            $totalDays = max(1, $dateStart->diffInDays($dateEnd));
            $elapsed = $today->lt($dateStart) ? 0 : ($today->gt($dateEnd) ? $totalDays : $dateStart->diffInDays($today));
            $timePct = min(100, round($elapsed / $totalDays * 100, 1));
            $daysLeft = $today->gt($dateEnd) ? 0 : $today->diffInDays($dateEnd);
        }

        [$healthLabel, $healthHex] = $this->health($budgetPct, $timePct);

        return compact('budgetAlloc', 'budgetUsed', 'budgetPct', 'budgetRemain', 'dateStart', 'dateEnd', 'timePct', 'daysLeft', 'totalDays', 'healthLabel', 'healthHex');
    }

    private function health(?float $budgetPct, ?float $timePct): array
    {
        if ($budgetPct === null && $timePct === null) {
            return ['No data', 'var(--gray-300)'];
        }

        $b = $budgetPct ?? 0;
        $t = $timePct ?? 0;

        if ($b >= 100 || $b > $t + 20) {
            return ['Over Budget / Behind', '#EF4444'];
        }
        if ($b > $t + 10 || ($t >= 90 && $b > 80)) {
            return ['At Risk', '#F59E0B'];
        }

        return ['On Track', '#10B981'];
    }

    private function activeTrainers()
    {
        return User::where('role', 'trainer')->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
    }

    private function allPrograms()
    {
        return Program::orderBy('title')->get(['id', 'title']);
    }

    /**
     * budget_allocated is only ever settable at creation — $includeBudgetAllocated
     * is false for update(), so a submitted value there is simply not validated
     * (and so never reaches the write array). See "LOCK BUDGET COMPLETELY".
     */
    private function trainingRules(bool $includeBudgetAllocated = true): array
    {
        $rules = [
            'title'               => 'required|string|max:200',
            'area'                => 'required|string|max:120',
            'description'         => 'nullable|string',
            'date_start'          => 'nullable|date',
            'date_end'            => 'nullable|date',
            'budget_used'         => 'nullable|numeric|min:0|max:9999999999.99',
            'status'              => ['nullable', Rule::in(['Proposed', 'Approved', 'Ongoing', 'Completed'])],
            'target_participants' => 'nullable|integer|min:1',
        ];

        if ($includeBudgetAllocated) {
            $rules['budget_allocated'] = 'nullable|numeric|min:0|max:9999999999.99';
        }

        return $rules;
    }

    /** lead_id/member_ids validation, shared by create() and update(). */
    private function teamRules(): array
    {
        return [
            'lead_id'       => 'required|integer|exists:users,id',
            'member_ids'    => 'nullable|array|max:3',
            'member_ids.*'  => 'integer|distinct|exists:users,id',
        ];
    }

    private function create(Request $request)
    {
        $this->scrubMemberIds($request);

        $data = Validator::make($request->all(), $this->trainingRules() + $this->teamRules() + [
            // Every new Activity must be nested under a Program from now on.
            // Pre-existing Activities may still have program_id = null; that's
            // untouched here since this path only ever creates new rows.
            'program_id' => 'required|integer|exists:programs,id',
        ], [
            'member_ids.max' => 'You can assign at most 3 team members.',
        ])->after(function (ValidatorContract $validator) use ($request) {
            $this->validateTeamRoles($validator, (int) $request->input('lead_id'), (array) $request->input('member_ids', []));
        })->validate();

        $training = DB::transaction(function () use ($data) {
            $training = Training::create([
                'title'                => $data['title'],
                'area'                 => $data['area'],
                'description'          => $data['description'] ?? null,
                'date_start'           => $data['date_start'] ?? null,
                'date_end'             => $data['date_end'] ?? null,
                'status'               => $data['status'] ?? 'Proposed',
                'target_participants'  => $data['target_participants'] ?? 0,
                'budget_allocated'     => $data['budget_allocated'] ?? null,
                'budget_used'          => $data['budget_used'] ?? 0,
                'created_by'           => Auth::guard('web')->id(),
                'program_id'           => $data['program_id'],
            ]);

            $this->syncTeam($training, (int) $data['lead_id'], $data['member_ids'] ?? []);

            return $training;
        });

        return redirect()->route('ec.trainings', ['view' => $training->id])->with('success', 'Activity created.');
    }

    private function update(Request $request)
    {
        $this->scrubMemberIds($request);

        // trainingRules(false): budget_allocated is not accepted here at all —
        // it's permanently fixed at creation, no path to change it afterward.
        $data = Validator::make($request->all(), $this->trainingRules(false) + $this->teamRules() + [
            'training_id' => 'required|integer|exists:trainings,id',
            // Nullable here (unlike create()) so editing a pre-existing,
            // not-yet-nested Activity doesn't force EC to pick a program
            // just to change something unrelated. They can still assign one.
            'program_id'  => 'nullable|integer|exists:programs,id',
        ], [
            'member_ids.max' => 'You can assign at most 3 team members.',
        ])->after(function (ValidatorContract $validator) use ($request) {
            $this->validateTeamRoles($validator, (int) $request->input('lead_id'), (array) $request->input('member_ids', []));
        })->validate();

        $training = Training::findOrFail($data['training_id']);

        DB::transaction(function () use ($training, $data) {
            $training->update([
                'title'                => $data['title'],
                'area'                 => $data['area'],
                'description'          => $data['description'] ?? null,
                'date_start'           => $data['date_start'] ?? null,
                'date_end'             => $data['date_end'] ?? null,
                'status'               => $data['status'] ?? 'Proposed',
                'target_participants'  => $data['target_participants'] ?? 0,
                'budget_used'          => $data['budget_used'] ?? 0,
                'program_id'           => $data['program_id'] ?? null,
                // budget_allocated deliberately absent — see trainingRules(false) above.
            ]);

            $this->syncTeam($training, (int) $data['lead_id'], $data['member_ids'] ?? []);
        });

        return redirect()->route('ec.trainings', ['view' => $data['training_id']])->with('success', 'Activity updated.');
    }

    /**
     * Wholesale-replaces this activity's team (activity_team_members) and
     * keeps trainer_id in sync with the lead. trainer_id is no longer
     * directly user-editable — every EC/Trainer controller that still reads
     * that column (EC's evaluation notifications, skills form display, etc.)
     * keeps working unchanged. A trainer added only as a 'member' is picked
     * up separately by Training::visibleToTrainer(), used throughout the
     * Trainer role's own controllers.
     */
    private function syncTeam(Training $training, int $leadId, array $memberIds): void
    {
        $training->teamMembers()->detach();

        $training->teamMembers()->attach($leadId, ['member_role' => 'lead']);
        foreach (array_unique($memberIds) as $memberId) {
            $training->teamMembers()->attach((int) $memberId, ['member_role' => 'member']);
        }

        $training->update(['trainer_id' => $leadId]);
    }

    private function delete(Request $request)
    {
        Training::where('id', (int) $request->input('training_id'))->delete();

        return redirect()->route('ec.trainings')->with('success', 'Activity deleted.');
    }

    private function updateStatus(Request $request)
    {
        $data = Validator::make($request->all(), [
            'training_id' => 'required|exists:trainings,id',
            'status'      => ['required', Rule::in(['Proposed', 'Approved', 'Ongoing', 'Completed'])],
        ])->validate();

        Training::where('id', $data['training_id'])->update(['status' => $data['status']]);

        return back()->with('success', 'Activity status updated.');
    }

    /** Only budget_used is writable here — budget_allocated is locked for good after creation. */
    private function updateBudget(Request $request)
    {
        $data = Validator::make($request->all(), [
            'training_id' => 'required|integer|exists:trainings,id',
            'budget_used' => 'nullable|numeric|min:0|max:9999999999.99',
        ])->validate();

        Training::where('id', $data['training_id'])->update([
            'budget_used' => $data['budget_used'] ?? 0,
        ]);

        return redirect()->route('ec.trainings', ['view' => $data['training_id']])->with('success', 'Budget usage updated successfully.');
    }

    /** This activity's own document repository — file or link, stamped with activity_id (not the legacy training_id). */
    private function uploadDocument(Request $request)
    {
        $activityId = (int) $request->input('activity_id');

        [$ok, $error] = $this->storeDocumentUpload($request, Auth::guard('web')->id(), [
            'activity_id' => $activityId,
        ]);

        if (! $ok) {
            return back()->with('error', $error);
        }

        return redirect()->route('ec.trainings', ['view' => $activityId])->with('success', 'Document added to activity.');
    }

    /** EC can always change an activity's cover picture — no ownership check needed here. */
    private function uploadCover(Request $request)
    {
        $trainingId = (int) $request->input('training_id');
        $training = Training::findOrFail($trainingId);

        [$ok, $error] = $this->storeCoverImage($request, $training);

        if (! $ok) {
            return back()->with('error', $error);
        }

        return redirect()->route('ec.trainings', ['view' => $trainingId])->with('success', 'Display picture updated.');
    }
}
