<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\Document;
use App\Models\EvalForm;
use App\Models\Participant;
use App\Models\Program;
use App\Models\SkillsForm;
use App\Models\Training;
use App\Support\FileSize;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        // Grouped by the fixed Activity Type options (see
        // resources/views/partials/activity-type-fields.blade.php) rather
        // than the raw `area` column's distinct values  -  that used to
        // produce one bar per free-text value (often an activity's own
        // title, from before Activity Type existed), which was both
        // meaningless as a "by type" breakdown and, with every bar sitting
        // at a count of 1, forced Chart.js into fractional x-axis ticks
        // (0, 0.2, 0.4 ...). All 8 types are always shown, in this fixed
        // order, even at zero, so the chart reads as a real type
        // breakdown; anything that isn't one of the 7 named types (an
        // older free-text value, or a custom "Other" entry) is folded into
        // "Other".
        $activityTypes = ['Training', 'Seminar', 'Workshop', 'Orientation', 'Forum', 'Conference', 'Consultation'];
        $areaTally = Training::pluck('area')
            ->map(fn ($area) => in_array($area, $activityTypes, true) ? $area : 'Other')
            ->countBy();
        $byArea = collect($activityTypes)
            ->push('Other')
            ->map(fn ($type) => (object) ['area' => $type, 'cnt' => (int) ($areaTally[$type] ?? 0)]);

        // Training::status is a derived/virtual attribute (see Training::getStatusAttribute() /
        // Training::booted()'s saving hook) computed from date_start/date_end at save time. The
        // stored `status` column is therefore only a snapshot as of the last save and goes stale
        // for any row that hasn't been re-saved since its dates rolled over — which is why a raw
        // `groupBy('status')` on the column used to bucket almost everything under "Proposed".
        // Recompute status the same way the model does, directly in SQL, so counts always reflect
        // each activity's real current status.
        $statusExpr = "CASE
            WHEN date_start IS NULL OR CURRENT_DATE < date_start THEN 'Proposed'
            WHEN date_end IS NULL OR CURRENT_DATE <= date_end THEN 'Ongoing'
            ELSE 'Completed'
        END";

        // IMPORTANT: this must NOT be built via Training::selectRaw(...)->get() (an Eloquent
        // query). Training defines getStatusAttribute(), and an Eloquent accessor always wins
        // over a raw selected column of the same name — so ->pluck('status') would silently
        // call getStatusAttribute() on each hydrated model instead of returning the aliased SQL
        // value. Since this query doesn't select date_start/date_end, that accessor then sees
        // null dates on every row and returns 'Proposed' for all of them (this was the actual
        // "still all Proposed" bug — the CASE expression itself was already correct). Querying
        // the table directly via DB::table() returns plain stdClass rows with no accessors, so
        // the computed status survives untouched.
        $byStatus = DB::table('trainings')
            ->selectRaw("{$statusExpr} as status, count(*)::int as cnt")
            ->groupBy(DB::raw($statusExpr))
            ->get();

        $statusCounts = $byStatus->pluck('cnt', 'status');

        // Explicit status => color map so the doughnut's colors are tied to the status NAME,
        // not to whatever order the query happens to return statuses in.
        $statusColorMap = [
            'Proposed'  => '#6B7280', // gray — not yet started
            'Ongoing'   => '#F59E0B', // amber — in progress
            'Completed' => '#10B981', // green — done
            'Cancelled' => '#EF4444', // red
        ];

        $beneficiaries = Beneficiary::query()
            ->leftJoin('participants', 'participants.beneficiary_id', '=', 'beneficiaries.id')
            ->leftJoin('trainings', 'trainings.id', '=', 'participants.training_id')
            ->selectRaw(
                "beneficiaries.id, beneficiaries.first_name, beneficiaries.last_name,
                 beneficiaries.email, beneficiaries.address, beneficiaries.created_at,
                 count(distinct participants.training_id)::int as enrolled_count,
                 string_agg(distinct trainings.title, ', ' order by trainings.title) as trainings_list"
            )
            ->groupBy('beneficiaries.id')
            ->orderByDesc('beneficiaries.created_at')
            ->get();

        $partPerTraining = Training::query()
            ->leftJoin('participants', 'participants.training_id', '=', 'trainings.id')
            ->selectRaw('trainings.id, trainings.title, count(participants.id)::int as cnt')
            ->groupBy('trainings.id')
            ->orderByDesc('cnt')
            ->get();

        $skillsForms = SkillsForm::with(['training:id,title,area', 'responses'])
            ->orderByDesc('sent_at')
            ->get();
        $this->attachTotalPax($skillsForms);

        $evalForms = EvalForm::with(['training:id,title,area', 'responses'])
            ->orderByDesc('sent_at')
            ->get();
        $this->attachTotalPax($evalForms);

        // Document Analysis — formerly the standalone Ec\AnalyticsController /
        // ec.analytics page, folded into this Reports page below the
        // Beneficiary Report section. `byScope` (Dashboard/Program/Activity
        // scope breakdown) isn't carried over: its chart ("By Scope") was
        // already removed from the page before this merge, so that query
        // would just be dead weight here.
        $documents = Document::all(['id', 'file_type', 'file_size', 'link_url', 'link_type', 'program_id', 'activity_id', 'created_at']);
        $totalDocuments = $documents->count();
        $totalStorage = FileSize::human((int) $documents->sum('file_size'));
        $byType = $this->documentsByType($documents);
        $uploadsByMonth = $this->uploadsByMonth($documents);
        $topByCount = $this->topDocumentsByCount();
        $fileVsLink = [
            // An uploaded file always has file_type set; a link never does
            // (see Document::booted() — exactly one of file_name/link_url,
            // and file_type is only ever set alongside file_name).
            'file' => $documents->filter(fn ($d) => filled($d->file_type))->count(),
            'link' => $documents->filter(fn ($d) => filled($d->link_url))->count(),
        ];

        return view('ec.reports', [
            'activePage'      => 'reports',
            'totalTrainings'  => Training::count(),
            'totalPrograms'   => Program::count(),
            'completedCount'  => (int) ($statusCounts['Completed'] ?? 0),
            'ongoingCount'    => (int) ($statusCounts['Ongoing'] ?? 0),
            'proposedCount'   => (int) ($statusCounts['Proposed'] ?? 0),
            'byArea'          => $byArea,
            'byStatus'        => $byStatus,
            'statusColorMap'  => $statusColorMap,
            'beneficiaries'   => $beneficiaries,
            'partPerTraining' => $partPerTraining,
            'skillsForms'     => $skillsForms,
            'evalForms'       => $evalForms,
            'totalDocuments'  => $totalDocuments,
            'totalStorage'    => $totalStorage,
            'byType'          => $byType,
            'uploadsByMonth'  => $uploadsByMonth,
            'topByCount'      => $topByCount,
            'fileVsLink'      => $fileVsLink,
        ]);
    }

    /**
     * Attach total_pax (participant count of the form's training) to each
     * form, matching the original's COUNT(DISTINCT p.id) alias.
     */
    private function attachTotalPax(Collection $forms): void
    {
        $trainingIds = $forms->pluck('training_id')->unique();

        $counts = Participant::whereIn('training_id', $trainingIds)
            ->selectRaw('training_id, count(*) as cnt')
            ->groupBy('training_id')
            ->pluck('cnt', 'training_id');

        foreach ($forms as $form) {
            $form->total_pax = (int) ($counts[$form->training_id] ?? 0);
        }
    }

    /** Uploaded files grouped by extension, links grouped by link_type — one combined "content type" breakdown. */
    private function documentsByType(Collection $documents): array
    {
        return $documents
            ->groupBy(fn ($d) => $d->file_type ?: ($d->link_type ? 'link: ' . $d->link_type : 'unknown'))
            ->map->count()
            ->sortDesc()
            ->all();
    }

    /** Rolling 12-month window (this month + the 11 before it), zero-filled so a quiet month still shows as 0 rather than a gap. */
    private function uploadsByMonth(Collection $documents): array
    {
        $months = collect(range(11, 0))->mapWithKeys(function ($i) {
            return [now()->subMonths($i)->format('Y-m') => now()->subMonths($i)->format('M Y')];
        });

        $counts = $months->map(fn () => 0)->all();
        foreach ($documents as $doc) {
            $key = Carbon::parse($doc->created_at)->format('Y-m');
            if (array_key_exists($key, $counts)) {
                $counts[$key]++;
            }
        }

        return [
            'labels' => $months->values()->all(),
            'counts' => array_values($counts),
        ];
    }

    /** Top 5 programs+activities combined, ranked by their own document count. */
    private function topDocumentsByCount(): array
    {
        $topPrograms = Document::query()
            ->whereNotNull('program_id')
            ->selectRaw('program_id, count(*) as doc_count')
            ->groupBy('program_id')
            ->with('program:id,title')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->program?->title ?? 'Deleted Program',
                'type'  => 'Program',
                'count' => $row->doc_count,
            ]);

        $topActivities = Document::query()
            ->whereNotNull('activity_id')
            ->selectRaw('activity_id, count(*) as doc_count')
            ->groupBy('activity_id')
            ->with('activity:id,title')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->activity?->title ?? 'Deleted Activity',
                'type'  => 'Activity',
                'count' => $row->doc_count,
            ]);

        return $topPrograms->concat($topActivities)
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->all();
    }
}
