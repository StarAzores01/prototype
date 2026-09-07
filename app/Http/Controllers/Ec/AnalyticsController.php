<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Support\FileSize;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Document Analytics — every metric here is computed straight from the
 * existing `documents` table (file_type, file_size, link_url/link_type,
 * program_id/activity_id, created_at). Nothing new is tracked: this app
 * doesn't record download events anywhere, so a "most downloaded" or
 * "views over time" metric isn't possible without a new events table —
 * out of scope here since it wasn't asked for, but flagging it since any
 * future ask like that would need new tracking infrastructure, not just
 * a new query.
 */
class AnalyticsController extends Controller
{
    public function index()
    {
        $documents = Document::all(['id', 'file_type', 'file_size', 'link_url', 'link_type', 'program_id', 'activity_id', 'created_at']);

        return view('ec.analytics', [
            'activePage'      => 'analytics',
            'totalDocuments'  => $documents->count(),
            'totalStorage'    => FileSize::human((int) $documents->sum('file_size')),
            'byType'          => $this->byType($documents),
            'uploadsByMonth'  => $this->uploadsByMonth($documents),
            'byScope'         => $this->byScope($documents),
            'topByCount'      => $this->topByCount(),
            'fileVsLink'      => [
                // An uploaded file always has file_type set; a link never does
                // (see Document::booted() — exactly one of file_name/link_url,
                // and file_type is only ever set alongside file_name).
                'file' => $documents->filter(fn ($d) => filled($d->file_type))->count(),
                'link' => $documents->filter(fn ($d) => filled($d->link_url))->count(),
            ],
        ]);
    }

    /** Uploaded files grouped by extension, links grouped by link_type — one combined "content type" breakdown. */
    private function byType(Collection $documents): array
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

    /**
     * Dashboard-level (no program_id, no activity_id) vs. program-scoped
     * vs. activity-scoped — the three cases are mutually exclusive by
     * construction (see HandlesDocumentUploads: each caller stamps exactly
     * one of program_id/activity_id, or neither).
     */
    private function byScope(Collection $documents): array
    {
        return [
            'Dashboard-level' => $documents->filter(fn ($d) => blank($d->program_id) && blank($d->activity_id))->count(),
            'Program-scoped'  => $documents->filter(fn ($d) => filled($d->program_id))->count(),
            'Activity-scoped' => $documents->filter(fn ($d) => filled($d->activity_id))->count(),
        ];
    }

    /** Top 5 programs+activities combined, ranked by their own document count. */
    private function topByCount(): array
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
