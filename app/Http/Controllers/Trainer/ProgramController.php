<?php

namespace App\Http\Controllers\Trainer;

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

/**
 * Trainer-role Programs — a SCOPED version of Ec\ProgramController, not full
 * parity. A trainer only ever sees programs they're on the team of (lead or
 * member — Program::scopeVisibleToTrainer()), and several actions that are
 * unrestricted for EC are lead-only here:
 *
 *  - updateTeam(): lead only, members get 403. There is no trainer-side
 *    timeline/extension action at all — only EC can extend a program's
 *    effective end date (Ec\ProgramController::extendTimeline()); a Project
 *    Lead is fully read-only on the timeline, same as any other member.
 *  - updateStatus(): also lead only (see the method doc for why this one
 *    was an inference call, not something the spec stated outright).
 *  - create(): the creating trainer is auto-attached as lead — there's no
 *    lead_id field on this role's create form at all.
 *
 * Adding an Activity under a program you belong to (lead OR member) stays
 * collaborative — that lives in Trainer\TrainingController::create(), not
 * here, same split as the Ec versions.
 */
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
                // Exists, but not this trainer's to see — 403, not a silent
                // fall-through to the list (that would just hide the link,
                // not actually stop a guessed URL).
                abort_unless($program->isVisibleTo($this->trainerId()), 403);

                return $this->detailView($program);
            }
            // Genuinely doesn't exist: same as Ec/Trainings — fall through to the list view.
        }

        return $this->listView();
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'create'          => $this->create($request),
            'update_status'   => $this->updateStatus($request),
            'update_team'     => $this->updateTeam($request),
            'upload'          => $this->uploadDocument($request),
            default            => back(),
        };
    }

    private function listView()
    {
        $programs = $this->withRollup(Program::visibleToTrainer($this->trainerId()))
            ->orderByDesc('created_at')
            ->get();

        return view('trainer.programs', [
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

        return view('trainer.programs', [
            'activePage'      => 'programs',
            'mode'            => 'detail',
            'viewProgram'     => $program,
            'viewActivities'  => $activities,
            'viewAmendments'  => $amendments,
            'viewDocuments'   => $documents,
            'trainers'        => $this->activeTrainers(),
            // Gates the Manage Team button + amendment-history note in the view.
            'isLead'          => $program->isLeadUser($this->trainerId()),
        ]);
    }

    /** Same rollup shape as Ec\ProgramController::rollup() — kept identical so the view partials/markup match 1:1. */
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

    /** Identical formula to Ec\ProgramController::rollup() — same static method name so the shared markup calling it doesn't care which role rendered the page. */
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

    /**
     * The creating trainer is automatically the lead — there's no lead_id
     * input on this role's create form, so it's never read from the
     * request at all (unlike Ec\ProgramController::create()).
     */
    private function create(Request $request)
    {
        $this->scrubMemberIds($request);
        $trainerId = $this->trainerId();

        $data = Validator::make($request->all(), [
            'title'             => 'required|string|max:200',
            'description'       => 'nullable|string',
            'area'              => 'required|string|max:120',
            'timeline_start'    => 'required|date',
            'timeline_end'      => 'required|date|after_or_equal:timeline_start',
            'budget_allocated'  => 'required|numeric|min:0|max:9999999999.99',
            'member_ids'        => 'nullable|array|max:3',
            'member_ids.*'      => 'integer|distinct|exists:users,id',
        ], [
            'member_ids.max' => 'You can assign at most 3 team members.',
        ])->after(function (ValidatorContract $validator) use ($request, $trainerId) {
            // The creating trainer is the lead for this check too, even though
            // there's no lead_id field — this still catches "picked myself as
            // a member as well", same rule EC's version enforces.
            $this->validateTeamRoles($validator, $trainerId, (array) $request->input('member_ids', []));
        })->validate();

        $program = DB::transaction(function () use ($data, $trainerId) {
            $program = Program::create([
                'title'            => $data['title'],
                'description'      => $data['description'] ?? null,
                'area'             => $data['area'],
                'timeline_start'   => $data['timeline_start'],
                'timeline_end'     => $data['timeline_end'],
                'budget_allocated' => $data['budget_allocated'],
                'status'           => 'Proposed',
                'created_by'       => $trainerId,
            ]);

            $program->teamMembers()->attach($trainerId, ['member_role' => 'lead']);
            foreach (array_unique($data['member_ids'] ?? []) as $memberId) {
                $program->teamMembers()->attach((int) $memberId, ['member_role' => 'member']);
            }

            return $program;
        });

        return redirect()->route('trainer.programs', ['view' => $program->id])->with('success', 'Program created.');
    }

    /**
     * Lead only. The spec didn't say this one outright the way it did for
     * updateTeam ("lead can freely change status, same as EC's version") —
     * I'm reading "lead" there as the acting role, not just an example, and
     * gating it the same way as the other authority action rather than
     * opening it to members. Flagged to the user as an inference call, not
     * something stated unambiguously.
     */
    private function updateStatus(Request $request)
    {
        $data = Validator::make($request->all(), [
            'program_id' => 'required|integer|exists:programs,id',
            'status'     => ['required', Rule::in(['Proposed', 'Approved', 'Ongoing', 'Completed'])],
        ])->validate();

        $program = Program::findOrFail($data['program_id']);
        $this->authorizeLead($program, 'Only this program\'s Project Lead can change its status.');

        $program->update(['status' => $data['status']]);

        return redirect()->route('trainer.programs', ['view' => $program->id])->with('success', 'Program status updated.');
    }

    /**
     * Lead only, and the lead role itself cannot be handed to someone else
     * through this action — only EC can transfer lead. The trainer-facing
     * form doesn't even render a lead_id field (it's a read-only display +
     * a hidden input carrying the current lead's id), but a tampered
     * request supplying a different lead_id is still explicitly rejected
     * here with a clear error rather than silently keeping the old lead
     * while applying the rest.
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
        $this->authorizeLead($program, 'Only this program\'s Project Lead can manage its team.');

        $currentLeadId = optional($program->lead->first())->id;
        if ((int) $data['lead_id'] !== (int) $currentLeadId) {
            return back()->withErrors([
                'lead_id' => 'Only EC can transfer the Project Lead to someone else — a Project Lead can\'t hand off their own role here.',
            ])->withInput();
        }

        DB::transaction(function () use ($program, $data, $currentLeadId) {
            // Detach members only — the lead pivot row is left untouched (re-detaching
            // and re-attaching the same lead_id would be a no-op anyway, but this
            // makes the "lead can't be reassigned here" guarantee explicit in code).
            $program->teamMembers()->wherePivot('member_role', 'member')->detach();

            foreach (array_unique($data['member_ids'] ?? []) as $memberId) {
                $program->teamMembers()->attach((int) $memberId, ['member_role' => 'member']);
            }
        });

        return redirect()->route('trainer.programs', ['view' => $program->id])->with('success', 'Program team updated.');
    }

    /** Program's own document repository — lead or member may both upload, same as viewing. */
    private function uploadDocument(Request $request)
    {
        $programId = (int) $request->input('program_id');
        $program = Program::find($programId);

        if (! $program || ! $program->isVisibleTo($this->trainerId())) {
            return back()->with('error', 'You are not on this program\'s team.');
        }

        [$ok, $error] = $this->storeDocumentUpload($request, Auth::guard('web')->id(), [
            'program_id' => $programId,
        ]);

        if (! $ok) {
            return back()->with('error', $error);
        }

        return redirect()->route('trainer.programs', ['view' => $programId])->with('success', 'Document added to program.');
    }

    private function authorizeLead(Program $program, string $message): void
    {
        abort_unless($program->isLeadUser($this->trainerId()), 403, $message);
    }

    private function trainerId(): ?int
    {
        return Auth::guard('web')->id();
    }
}
