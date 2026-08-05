<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Participant;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TrainingController extends Controller
{
    public function index(Request $request)
    {
        $viewId = (int) $request->query('view', 0);

        if ($viewId) {
            $training = Training::where('id', $viewId)->where('trainer_id', $this->trainerId())->first();
            if ($training) {
                return $this->detailView($training);
            }
            // No match (not found, or belongs to another trainer): original
            // falls through to the list view instead of a 404.
        }

        return $this->listView($request);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'update_budget' => $this->updateBudget($request),
            default         => back(),
        };
    }

    private function listView(Request $request)
    {
        $q = trim($request->query('q', ''));
        $status = $request->query('status', '');

        $query = Training::where('trainer_id', $this->trainerId())
            ->withCount('participants as trainees');

        if ($q) {
            $query->where('title', 'like', "%{$q}%");
        }
        if ($status) {
            $query->where('status', $status);
        }

        return view('trainer.trainings', [
            'activePage' => 'trainings',
            'mode'       => 'list',
            'trainings'  => $query->orderByDesc('date_start')->get(),
            'q'          => $q,
            'status'     => $status,
        ]);
    }

    private function detailView(Training $training)
    {
        $participants = Participant::with('evaluations')
            ->where('training_id', $training->id)
            ->orderBy('full_name')
            ->get();

        $documents = Document::where('training_id', $training->id)
            ->orderByDesc('created_at')
            ->get();

        return view('trainer.trainings', [
            'activePage'       => 'trainings',
            'mode'             => 'detail',
            'viewTraining'     => $training,
            'viewParticipants' => $participants,
            'viewDocs'         => $documents,
            'progress'         => $this->progress($training),
        ]);
    }

    /**
     * Budget/timeline percentages and the derived "health" pill — same
     * formula as the EC module's training detail view.
     */
    private function progress(Training $training): array
    {
        $budgetAlloc = (float) ($training->budget_allocated ?? 0);
        $budgetUsed = (float) ($training->budget_used ?? 0);
        $budgetPct = $budgetAlloc > 0 ? min(100, round($budgetUsed / $budgetAlloc * 100, 1)) : null;

        $dateStart = $training->date_start;
        $dateEnd = $training->date_end;
        $today = now()->startOfDay();
        $timePct = null;
        $daysLeft = null;

        if ($dateStart && $dateEnd) {
            $totalDays = max(1, $dateStart->diffInDays($dateEnd));
            $elapsed = $today->lt($dateStart) ? 0 : ($today->gt($dateEnd) ? $totalDays : $dateStart->diffInDays($today));
            $timePct = min(100, round($elapsed / $totalDays * 100, 1));
            $daysLeft = $today->gt($dateEnd) ? 0 : $today->diffInDays($dateEnd);
        }

        $healthHex = '#10B981';
        $healthLabel = 'On Track';
        if ($budgetPct !== null || $timePct !== null) {
            $b = $budgetPct ?? 0;
            $t = $timePct ?? 0;
            if ($b >= 100 || $b > $t + 20) {
                $healthHex = '#EF4444';
                $healthLabel = 'Over Budget / Behind';
            } elseif ($b > $t + 10 || ($t >= 90 && $b > 80)) {
                $healthHex = '#F59E0B';
                $healthLabel = 'At Risk';
            }
        }

        return compact('budgetAlloc', 'budgetUsed', 'budgetPct', 'dateStart', 'dateEnd', 'timePct', 'daysLeft', 'healthLabel', 'healthHex');
    }

    private function updateBudget(Request $request)
    {
        $data = Validator::make($request->all(), [
            'training_id'      => 'required|integer|exists:trainings,id',
            'budget_allocated' => 'nullable|numeric|min:0|max:9999999999.99',
            'budget_used'      => 'nullable|numeric|min:0|max:9999999999.99',
        ])->validate();

        $tid = (int) $data['training_id'];

        // Only allow if this training belongs to the logged-in trainer.
        $training = Training::where('id', $tid)->where('trainer_id', $this->trainerId())->first();

        if ($training) {
            $training->update([
                'budget_allocated' => $data['budget_allocated'] ?? null,
                'budget_used'      => $data['budget_used'] ?? 0,
            ]);

            return redirect()->route('trainer.trainings', ['view' => $tid])->with('success', 'Budget updated successfully.');
        }

        return redirect()->route('trainer.trainings', ['view' => $tid]);
    }

    private function trainerId(): ?int
    {
        return Auth::guard('web')->id();
    }
}
