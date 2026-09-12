<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Program;
use Illuminate\Support\Collection;

/**
 * Groups a flat collection of Document rows by their Parent Program, for the
 * dashboard Documents pages (Ec\DocumentController, Trainer\DocumentController)
 * — replaces the old "group by upload date" scheme entirely. A document
 * "belongs" to a program either directly (program_id) or indirectly (its
 * activity_id's Training itself has a program_id); everything else (no
 * program_id, and no activity — or an activity with no program of its own)
 * falls into the trailing "general" bucket, since there's nowhere else for
 * it to go.
 */
trait GroupsDocumentsByProgram
{
    /**
     * @return array{programGroups: Collection, general: Collection}
     *   programGroups: Collection of ['program' => Program, 'documents' => Collection (program-level, no activity), 'activities' => Collection of ['activity' => Training, 'documents' => Collection]]
     *   general: Collection of Documents with no resolvable parent program
     */
    private function groupDocumentsByProgram(Collection $docs): array
    {
        $resolveProgramId = function ($document) {
            return $document->program_id ?: optional($document->activity)->program_id;
        };

        $withProgram = $docs->filter(fn ($d) => (bool) $resolveProgramId($d))->values();
        $general = $docs->reject(fn ($d) => (bool) $resolveProgramId($d))->values();

        $byProgramId = $withProgram->groupBy($resolveProgramId);

        $programs = Program::whereIn('id', $byProgramId->keys())->orderBy('title')->get();

        $programGroups = $programs->map(function (Program $program) use ($byProgramId) {
            $docsForProgram = $byProgramId->get($program->id, collect());

            $direct = $docsForProgram->whereNull('activity_id')->values();

            $activities = $docsForProgram->whereNotNull('activity_id')
                ->groupBy('activity_id')
                ->map(fn ($group) => [
                    'activity'  => $group->first()->activity,
                    'documents' => $group->values(),
                ])
                ->sortBy(fn ($entry) => $entry['activity']->title ?? '')
                ->values();

            return [
                'program'    => $program,
                'documents'  => $direct,
                'activities' => $activities,
            ];
        })->values();

        return ['programGroups' => $programGroups, 'general' => $general];
    }
}
