<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Concerns\HandlesDocumentUploads;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Ec\Concerns\ValidatesTeamRoles;
use App\Models\Document;
use App\Models\Program;
use App\Models\ProgramAmendment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorContract;

class ProgramController extends Controller
{
    use ValidatesTeamRoles;
    use HandlesDocumentUploads;

    public function index(Request $request)
    {
        $viewId = (int) $request->query('view', 0);

        if ($viewId) {
            $program = Program::with(['creator', 'lead', 'members'])->find($viewId);
            if ($program) {
                return $this->detailView($program);
            }
            // No match: mirrors Trainings — fall through to the list view instead of a 404.
        }

        return $this->listView();
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'create'          => $this->create($request),
            'request_unlock'  => $this->requestUnlock($request),
            'update_status'   => $this->updateStatus($request),
            'update_team'     => $this->updateTeam($request),
            'upload'          => $this->uploadDocument($request),
            default            => back(),
        };
    }

    private function listView()
    {
        $programs = $this->withRollup(Program::query())
            ->orderByDesc('created_at')
            ->get();

        return view('ec.programs', [
            'activePage' => 'programs',
            'mode'       => 'list',
            'programs'   => $programs,
            'trainers'   => $this->activeTrainers(),
        ]);
    }

    private function detailView(Program $program)
    {
        $program = $this->withRollup(Program::query())->whereKey($program->id)->firstOrFail();

        $activities = $program->trainings()->orderByDesc('date_start')->get();

        $amendments = ProgramAmendment::with('amendedBy')
            ->where('program_id', $program->id)
            ->orderByDesc('created_at')
            ->get();

        $documents = Document::with('uploader')
            ->where('program_id', $program->id)
            ->orderByDesc('created_at')
            ->get();

        return view('ec.programs', [
            'activePage'      => 'programs',
            'mode'            => 'detail',
            'viewProgram'     => $program,
            'viewActivities'  => $activities,
            'viewAmendments'  => $amendments,
            'viewDocuments'   => $documents,
            'trainers'        => $this->activeTrainers(),
        ]);
    }

    /**
     * Progress rollup: % of this program's activities (trainings) that are
     * Completed, and remaining budget = budget_allocated - SUM(budget_used)
     * across every one of its activities.
     */
    private function withRollup($query)
    {
        return $query
            ->withCount([
                'trainings as activities_total_count',
                'trainings as activities_completed_count' => fn ($q) => $q->where('status', 'Completed'),
            ])
            ->withSum('trainings as activities_budget_used_sum', 'budget_used')
            ->with(['lead', 'members']);
    }

    /** Turns the withRollup() counts into a display-ready shape, same idea as Trainings' progress(). */
    public static function rollup(Program $program): array
    {
        $total = (int) ($program->activities_total_count ?? 0);
        $completed = (int) ($program->activities_completed_count ?? 0);
        $progressPct = $total > 0 ? round($completed / $total * 100) : 0;

        $budgetAlloc = (float) ($program->budget_allocated ?? 0);
        $budgetUsed = (float) ($program->activities_budget_used_sum ?? 0);
        $budgetRemain = $budgetAlloc - $budgetUsed;

        return compact('total', 'completed', 'progressPct', 'budgetAlloc', 'budgetUsed', 'budgetRemain');
    }

    private function activeTrainers()
    {
        return User::where('role', 'trainer')->where('is_active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);
    }

    private function create(Request $request)
    {
        $this->scrubMemberIds($request);

        $data = Validator::make($request->all(), [
            'title'             => 'required|string|max:200',
            'description'       => 'nullable|string',
            // Free text — no fixed area list anymore (was Rule::in(self::AREAS)).
            'area'              => 'required|string|max:120',
            'timeline_start'    => 'required|date',
            'timeline_end'      => 'required|date|after_or_equal:timeline_start',
            'budget_allocated'  => 'required|numeric|min:0|max:9999999999.99',
            'lead_id'           => 'required|integer|exists:users,id',
            'member_ids'        => 'nullable|array|max:3',
            'member_ids.*'      => 'integer|distinct|exists:users,id',
        ], [
            'member_ids.max' => 'You can assign at most 3 team members.',
        ])->after(function (ValidatorContract $validator) use ($request) {
            $this->validateTeamRoles($validator, (int) $request->input('lead_id'), (array) $request->input('member_ids', []));
        })->validate();

        $program = DB::transaction(function () use ($data) {
            $program = Program::create([
                'title'            => $data['title'],
                'description'      => $data['description'] ?? null,
                'area'             => $data['area'],
                'timeline_start'   => $data['timeline_start'],
                'timeline_end'     => $data['timeline_end'],
                'budget_allocated' => $data['budget_allocated'],
                'status'           => 'Proposed',
                'is_locked'        => true, // always locked on creation — no form field sets this.
                'created_by'       => Auth::guard('web')->id(),
            ]);

            $program->teamMembers()->attach((int) $data['lead_id'], ['member_role' => 'lead']);
            foreach (array_unique($data['member_ids'] ?? []) as $memberId) {
                $program->teamMembers()->attach((int) $memberId, ['member_role' => 'member']);
            }

            return $program;
        });

        return redirect()->route('ec.programs', ['view' => $program->id])->with('success', 'Program created.');
    }

    /**
     * The only way title/description/area/timeline can change once a
     * program exists: EC picks a field, supplies the new value plus a
     * required remark, and it's logged to program_amendments. is_locked
     * stays true throughout — this "unlocks" for the single write, not a
     * general switch.
     *
     * budget_allocated is NOT amendable — it's permanently fixed at
     * creation, full stop, not even through this path. 'budget' used to be
     * a valid field_changed value here; it's been removed entirely (see
     * the "LOCK BUDGET COMPLETELY" requirement). The only thing that can
     * still move the numbers is budget_used, logged at the Activity level
     * (Ec\TrainingController::updateBudget()) and rolled up by
     * Program::rollup() below.
     */
    private function requestUnlock(Request $request)
    {
        $data = Validator::make($request->all(), [
            'program_id'          => 'required|integer|exists:programs,id',
            'field_changed'       => ['required', Rule::in(['timeline'])],
            'remark'              => 'required|string|max:2000',
            'new_timeline_start'  => 'required|date',
            'new_timeline_end'    => 'required|date|after_or_equal:new_timeline_start',
        ])->validate();

        $program = Program::findOrFail($data['program_id']);

        DB::transaction(function () use ($program, $data) {
            $oldValue = $program->timeline_start->format('Y-m-d').' to '.$program->timeline_end->format('Y-m-d');
            $newValue = $data['new_timeline_start'].' to '.$data['new_timeline_end'];
            $program->timeline_start = $data['new_timeline_start'];
            $program->timeline_end = $data['new_timeline_end'];

            // Re-lock explicitly (it never actually left true) so intent stays obvious in code.
            $program->is_locked = true;
            $program->save();

            ProgramAmendment::create([
                'program_id'    => $program->id,
                'field_changed' => $data['field_changed'],
                'old_value'     => $oldValue,
                'new_value'     => $newValue,
                'remark'        => $data['remark'],
                'amended_by'    => Auth::guard('web')->id(),
            ]);
        });

        return redirect()->route('ec.programs', ['view' => $program->id])->with('success', 'Program amended and re-locked.');
    }

    /**
     * Status is not one of the two protected fields (budget/timeline) — EC
     * can move it freely, no remark, no program_amendments row.
     */
    private function updateStatus(Request $request)
    {
        $data = Validator::make($request->all(), [
            'program_id' => 'required|integer|exists:programs,id',
            'status'     => ['required', Rule::in(['Proposed', 'Approved', 'Ongoing', 'Completed'])],
        ])->validate();

        Program::where('id', $data['program_id'])->update(['status' => $data['status']]);

        return redirect()->route('ec.programs', ['view' => $data['program_id']])->with('success', 'Program status updated.');
    }

    /**
     * Reassigns the lead and/or members after creation. Re-runs the exact
     * same validateTeamRoles() check as create() — lead must be a trainer,
     * members must be trainers, no duplicates, max 3 members. Team
     * composition isn't a protected field either: this replaces the
     * program_team_members rows directly, no program_amendments entry.
     */
    private function updateTeam(Request $request)
    {
        $this->scrubMemberIds($request);

        $data = Validator::make($request->all(), [
            'program_id'    => 'required|integer|exists:programs,id',
            'lead_id'       => 'required|integer|exists:users,id',
            'member_ids'    => 'nullable|array|max:3',
            'member_ids.*'  => 'integer|distinct|exists:users,id',
        ], [
            'member_ids.max' => 'You can assign at most 3 team members.',
        ])->after(function (ValidatorContract $validator) use ($request) {
            $this->validateTeamRoles($validator, (int) $request->input('lead_id'), (array) $request->input('member_ids', []));
        })->validate();

        $program = Program::findOrFail($data['program_id']);

        DB::transaction(function () use ($program, $data) {
            $program->teamMembers()->detach(); // wholesale replace — not amendment-tracked.

            $program->teamMembers()->attach((int) $data['lead_id'], ['member_role' => 'lead']);
            foreach (array_unique($data['member_ids'] ?? []) as $memberId) {
                $program->teamMembers()->attach((int) $memberId, ['member_role' => 'member']);
            }
        });

        return redirect()->route('ec.programs', ['view' => $program->id])->with('success', 'Program team updated.');
    }

    /** Program's own general repository — file or link, stamped with this program's id. */
    private function uploadDocument(Request $request)
    {
        $programId = (int) $request->input('program_id');

        [$ok, $error] = $this->storeDocumentUpload($request, Auth::guard('web')->id(), [
            'program_id' => $programId,
        ]);

        if (! $ok) {
            return back()->with('error', $error);
        }

        return redirect()->route('ec.programs', ['view' => $programId])->with('success', 'Document added to program.');
    }
}
