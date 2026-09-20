<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\SkillProgressEntry;
use App\Models\SkillsForm;
use App\Models\SkillsResponse;
use Illuminate\Http\Request;

class SkillsController extends Controller
{
    public function index(Request $request)
    {
        $forms = SkillsForm::with(['training:id,title,date_start,trainer_id', 'training.trainer:id,first_name,last_name'])
            ->withCount('responses')
            ->orderByDesc('sent_at')
            ->get();

        $this->attachParticipantCounts($forms);

        $viewId = (int) $request->query('form', 0);
        $viewForm = null;
        $responses = [];

        if ($viewId) {
            $viewForm = $forms->firstWhere('id', $viewId);

            if ($viewForm) {
                $responses = SkillsResponse::with('beneficiary')
                    ->where('form_id', $viewId)
                    ->orderByDesc('submitted_at')
                    ->get();
            }
        }

        // Read-only monitoring of the beneficiary progress journal —
        // staff can view, never edit/delete a beneficiary's own entries.
        $progressEntries = SkillProgressEntry::with(['beneficiary:id,first_name,last_name', 'training:id,title'])
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->get();

        $totalRecordedEarnings = $progressEntries->sum('service_fee');

        // Grouped by the activity (training) the entry is tied to, so staff
        // can see a beneficiary's progress in context rather than one long
        // undifferentiated list. An entry logged without a training attached
        // (see Beneficiary\SkillsController::rules()) falls under its own
        // free-text activity_name instead.
        $entriesByActivity = $progressEntries->groupBy(
            fn ($e) => $e->training->title ?? $e->activity_name
        );

        return view('ec.skills', [
            'activePage'            => 'skills',
            'forms'                 => $forms,
            'viewForm'              => $viewForm,
            'responses'             => $responses,
            'progressEntries'       => $progressEntries,
            'entriesByActivity'     => $entriesByActivity,
            'totalRecordedEarnings' => $totalRecordedEarnings,
        ]);
    }

    /**
     * Total participants per form's training — one grouped query instead of
     * the original's per-row JOIN, then attached to each form as total_pax
     * (matching the original's COUNT(DISTINCT p.id) alias).
     */
    private function attachParticipantCounts($forms): void
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
}
