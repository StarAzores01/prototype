<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Concerns\HandlesCoverImageUpload;
use App\Http\Controllers\Concerns\HandlesDocumentUploads;
use App\Http\Controllers\Controller;
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

class TrainingController extends Controller
{
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
        // "Upcoming" is a display-only merge of Proposed+Approved  -  the
        // original filtered the raw status column against this label
        // directly, which could never match anything. Map it properly.
        if ($status === 'Upcoming') {
            $query->whereIn('status', ['Proposed', 'Approved']);
        } elseif (in_array($status, ['Ongoing', 'Completed'], true)) {
            $query->where('status', $status);
        }

        $trainings = $query->orderByRaw("CASE status WHEN 'Ongoing' THEN 0 WHEN 'Proposed' THEN 1 WHEN 'Completed' THEN 2 ELSE 3 END")
            ->orderBy('date_start')
            ->get();

        // Grouped by the linked Program ("Project") for the card grid  -
        // grouping by id (not title) so two programs that happen to share a
        // title never get merged into one section. Unassigned activities
        // (program_id null) land in their own group, key 0. groupBy()
        // preserves each group's first-occurrence order from $trainings
        // (already date_start desc), so sections naturally read most
        // recently scheduled first without any extra sort here.
        $trainingsByProject = $trainings->groupBy(fn ($t) => $t->program_id ?? 0);

        return view('ec.trainings', [
            'activePage'         => 'trainings',
            'mode'               => 'list',
            'trainings'          => $trainings,
            'trainingsByProject' => $trainingsByProject,
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
            'budgetItems'      => $training->budgetItems()->with('loggedBy')->get(),
        ]);
    }

    /**
     * Budget percentage and the derived "health" pill shown on the detail
     * view's Project Progress card. This used to also track a timeline
     * percentage (days elapsed vs. total)  -  the Activity-level "Timeline
     * Progress" monitoring it fed has been removed entirely (per-Activity
     * timeline monitoring is retired; Program-level timeline/extension
     * handling is untouched), so health is budget-only from here on.
     */
    private function progress(Training $training): array
    {
        $budgetAlloc = (float) ($training->budget_allocated ?? 0);
        $budgetUsed = (float) ($training->budget_used ?? 0);
        $budgetPct = $budgetAlloc > 0 ? min(100, round($budgetUsed / $budgetAlloc * 100, 1)) : null;
        $budgetRemain = $budgetAlloc > 0 ? $budgetAlloc - $budgetUsed : null;

        [$healthLabel, $healthHex] = $this->health($budgetPct);

        return compact('budgetAlloc', 'budgetUsed', 'budgetPct', 'budgetRemain', 'healthLabel', 'healthHex');
    }

    /** Same thresholds already used to color the Budget Utilization bar, so the pill and the bar always agree. */
    private function health(?float $budgetPct): array
    {
        if ($budgetPct === null) {
            return ['No data', 'var(--gray-300)'];
        }
        if ($budgetPct >= 100) {
            return ['Over Budget', '#EF4444'];
        }
        if ($budgetPct >= 80) {
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
        // Must include timeline_start/timeline_end/extended_end_date, not
        // just id+title: ec.partials.activity-create-fields renders each
        // option's data-start/data-end from $prog->timeline_start and
        // $prog->effective_end_date (which reads extended_end_date ??
        // timeline_end) to bound the activity's date pickers to the chosen
        // program's timeline. Selecting only id/title left those two
        // always null, so the bounds were silently never applied and any
        // date could be typed in, regardless of the program picked.
        return Program::orderBy('title')->get(['id', 'title', 'timeline_start', 'timeline_end', 'extended_end_date']);
    }

    /**
     * budget_allocated is only ever settable at creation  -  $includeBudgetAllocated
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
            'date_end'            => 'nullable|date|after_or_equal:date_start',
            'budget_used'         => 'nullable|numeric|min:0|max:9999999999.99',
            'target_participants' => 'nullable|integer|min:1',
        ];

        if ($includeBudgetAllocated) {
            $rules['budget_allocated'] = 'nullable|numeric|min:0|max:9999999999.99';
        }

        return $rules;
    }

    /**
     * An activity doesn't have its own Project Leader / Team Members
     * anymore  -  it inherits whoever is assigned to its Program. Returns
     * null lead_id (and no members) when the program has no lead yet, so
     * callers can skip syncTeam() rather than attach a nonexistent user id.
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

    private function create(Request $request)
    {
        $data = Validator::make($request->all(), $this->trainingRules() + [
            'program_id' => 'required|integer|exists:programs,id',
        ])->after(function ($validator) use ($request) {
            $programId = (int) $request->input('program_id');
            $program   = $programId ? \App\Models\Program::find($programId) : null;
            if (! $program) return;

            $start = $request->input('date_start');
            $end   = $request->input('date_end');
            $pStart = $program->timeline_start?->format('Y-m-d');
            $pEnd   = $program->effective_end_date?->format('Y-m-d');

            if ($start && $pStart && $start < $pStart) {
                $validator->errors()->add('date_start', 'Start date cannot be before the program\'s start date ('.$pStart.').');
            }
            if ($start && $pEnd && $start > $pEnd) {
                $validator->errors()->add('date_start', 'Start date cannot be after the program\'s end date ('.$pEnd.').');
            }
            if ($end && $pStart && $end < $pStart) {
                $validator->errors()->add('date_end', 'End date cannot be before the program\'s start date ('.$pStart.').');
            }
            if ($end && $pEnd && $end > $pEnd) {
                $validator->errors()->add('date_end', 'End date cannot be after the program\'s end date ('.$pEnd.').');
            }
        })->validate();

        $training = DB::transaction(function () use ($data, $request) {
            // Compute budget_used from line items if provided, otherwise default 0.
            $budgetItems = $request->input('budget_items', []);
            $budgetUsed  = 0;
            foreach ($budgetItems as $item) {
                $budgetUsed += round((float)($item['quantity'] ?? 0) * (float)($item['unit_cost'] ?? 0), 2);
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
                'created_by'           => Auth::guard('web')->id(),
                'program_id'           => $data['program_id'],
            ]);

            // Save budget breakdown items.
            $userId = Auth::guard('web')->id();
            foreach ($budgetItems as $item) {
                $cat = ($item['category'] ?? '') === 'Other'
                    ? 'Other: '.trim($item['other_specify'] ?? '')
                    : ($item['category'] ?? '');
                if (! $cat) continue;
                $qty = (float)($item['quantity'] ?? 0);
                $uc  = (float)($item['unit_cost'] ?? 0);
                if ($qty <= 0) continue;
                \App\Models\BudgetItem::create([
                    'training_id' => $training->id,
                    'category'    => $cat,
                    'description' => $item['description'] ?? null,
                    'quantity'    => $qty,
                    'unit_cost'   => $uc,
                    'logged_by'   => $userId,
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
            'budget_allocated' => (float) ($training->budget_allocated ?? 0),
            'budget_used'    => (float) $training->budget_used,
            'item_count'     => count($request->input('budget_items', [])),
        ]);

        return redirect()->route('ec.trainings', ['view' => $training->id])->with('success', 'Activity created.');
    }

    private function update(Request $request)
    {
        // trainingRules(false): budget_allocated is not accepted here at all  -
        // it's permanently fixed at creation, no path to change it afterward.
        $data = Validator::make($request->all(), $this->trainingRules(false) + [
            'training_id' => 'required|integer|exists:trainings,id',
            // Nullable here (unlike create()) so editing a pre-existing,
            // not-yet-nested Activity doesn't force EC to pick a program
            // just to change something unrelated. They can still assign one.
            'program_id'  => 'nullable|integer|exists:programs,id',
        ])->validate();

        $training = Training::findOrFail($data['training_id']);

        DB::transaction(function () use ($training, $data) {
            $training->update([
                'title'                => $data['title'],
                'area'                 => $data['area'],
                'description'          => $data['description'] ?? null,
                'date_start'           => $data['date_start'] ?? null,
                'date_end'             => $data['date_end'] ?? null,
                'target_participants'  => $data['target_participants'] ?? 0,
                'budget_used'          => $data['budget_used'] ?? 0,
                'program_id'           => $data['program_id'] ?? null,
                // budget_allocated deliberately absent  -  see trainingRules(false) above.
            ]);

            // Re-sync the inherited team whenever a program is assigned  -
            // covers both "picked a program for the first time" and
            // "switched to a different program". No program (still) assigned
            // means nothing to inherit from, so the existing team (if any,
            // from before this became inherited-only) is left untouched
            // rather than cleared.
            if ($data['program_id'] ?? null) {
                $team = $this->teamFromProgram($data['program_id']);
                if ($team['lead_id']) {
                    $this->syncTeam($training, $team['lead_id'], $team['member_ids']);
                }
            }
        });

        return redirect()->route('ec.trainings', ['view' => $data['training_id']])->with('success', 'Activity updated.');
    }

    /**
     * Wholesale-replaces this activity's team (activity_team_members) and
     * keeps trainer_id in sync with the lead. trainer_id is no longer
     * directly user-editable  -  every EC/Trainer controller that still reads
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

        $trainingId = $data['training_id'];
        $userId     = Auth::guard('web')->id();

        DB::transaction(function () use ($data, $trainingId, $userId) {
            \App\Models\BudgetItem::where('training_id', $trainingId)->delete();

            $total = 0;
            foreach ($data['items'] as $item) {
                $category = $item['category'] === 'Other'
                    ? 'Other: '.trim($item['other_specify'] ?? '')
                    : $item['category'];

                $qty      = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $lineTotal = round($qty * $unitCost, 2);
                $total    += $lineTotal;

                \App\Models\BudgetItem::create([
                    'training_id' => $trainingId,
                    'category'    => $category,
                    'description' => $item['description'] ?? null,
                    'quantity'    => $qty,
                    'unit_cost'   => $unitCost,
                    'logged_by'   => $userId,
                ]);
            }

            Training::where('id', $trainingId)->update(['budget_used' => $total]);
        });

        $training = Training::findOrFail($trainingId);
        ProgramLogService::recordBudget($training, ProgramLogService::ACTION_BUDGET_LOGGED, [
            'stage'      => 'update',
            'budget_used' => (float) $training->budget_used,
            'item_count' => count($data['items']),
        ]);

        return redirect()->route('ec.trainings', ['view' => $trainingId, 'budget_saved' => 1])->with('success', 'Budget breakdown saved.');
    }

    /** This activity's own document repository  -  file or link, stamped with activity_id (not the legacy training_id). */
    private function uploadDocument(Request $request)
    {
        $activityId = (int) $request->input('activity_id');

        [$ok, $error, $document] = $this->storeDocumentUpload($request, Auth::guard('web')->id(), [
            'activity_id' => $activityId,
        ]);

        if (! $ok) {
            return back()->with('error', $error);
        }

        ProgramLogService::recordDocument(
            $document,
            $document->isLink() ? ProgramLogService::ACTION_ADDED_LINK : ProgramLogService::ACTION_UPLOADED_FILE
        );

        return redirect()->route('ec.trainings', ['view' => $activityId])->with('success', 'Document added to activity.');
    }

    /** EC can always change an activity's cover picture  -  no ownership check needed here. */
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

