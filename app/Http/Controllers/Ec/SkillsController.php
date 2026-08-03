<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Participant;
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

        return view('ec.skills', [
            'activePage' => 'skills',
            'forms'      => $forms,
            'viewForm'   => $viewForm,
            'responses'  => $responses,
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
