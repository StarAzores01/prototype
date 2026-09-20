<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\EvalForm;
use App\Models\Participant;
use App\Models\Program;
use App\Models\SkillsForm;
use App\Models\Training;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $byArea = Training::selectRaw('area, count(*)::int as cnt')
            ->groupBy('area')
            ->orderByDesc('cnt')
            ->get();

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

        $byStatus = DB::table('trainings')
            ->selectRaw("{$statusExpr} as status, count(*)::int as cnt")
            ->groupBy(DB::raw($statusExpr))
            ->get();

        $statusCounts = $byStatus->pluck('cnt', 'status');

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
            'totalPrograms'   => Program::count(),
            'completedCount'  => (int) ($statusCounts['Completed'] ?? 0),
            'ongoingCount'    => (int) ($statusCounts['Ongoing'] ?? 0),
            'proposedCount'   => (int) ($statusCounts['Proposed'] ?? 0),
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
