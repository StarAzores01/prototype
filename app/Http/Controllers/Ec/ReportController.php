<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\EvalForm;
use App\Models\Participant;
use App\Models\SkillsForm;
use App\Models\Training;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    public function index()
    {
        $byArea = Training::selectRaw('area, count(*)::int as cnt')
            ->groupBy('area')
            ->orderByDesc('cnt')
            ->get();

        $byStatus = Training::selectRaw('status, count(*)::int as cnt')
            ->groupBy('status')
            ->get();

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

        return view('ec.reports', [
            'activePage'      => 'reports',
            'totalTrainings'  => Training::count(),
            'completedCount'  => Training::where('status', 'Completed')->count(),
            'ongoingCount'    => Training::where('status', 'Ongoing')->count(),
            'proposedCount'   => Training::where('status', 'Proposed')->count(),
            'totalPart'       => Participant::count(),
            'totalBen'        => Beneficiary::count(),
            'byArea'          => $byArea,
            'byStatus'        => $byStatus,
            'beneficiaries'   => $beneficiaries,
            'partPerTraining' => $partPerTraining,
            'skillsForms'     => $skillsForms,
            'evalForms'       => $evalForms,
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
}
