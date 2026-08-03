<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Participant;
use App\Models\Training;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TrainingController extends Controller
{
    public function index(Request $request)
    {
        $viewId = (int) $request->query('view', 0);

        if ($viewId) {
            $training = Training::with('trainer')->find($viewId);
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
            'update_status' => $this->updateStatus($request),
            'update_budget' => $this->updateBudget($request),
            default         => back(),
        };
    }

    private function listView(Request $request)
    {
        $q = trim($request->query('q', ''));
        $status = $request->query('status', '');
        $area = $request->query('area', '');

        $query = Training::with('trainer')->withCount('participants as enrolled');

        if ($q) {
            $query->where(function ($queryBuilder) use ($q) {
                $queryBuilder->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }
        if ($area) {
            $query->where('area', $area);
        }
        // "Upcoming" is a display-only merge of Proposed+Approved — the
        // original filtered the raw status column against this label
        // directly, which could never match anything. Map it properly.
        if ($status === 'Upcoming') {
            $query->whereIn('status', ['Proposed', 'Approved']);
        } elseif (in_array($status, ['Ongoing', 'Completed'], true)) {
            $query->where('status', $status);
        }

        return view('ec.trainings', [
            'activePage' => 'trainings',
            'mode'       => 'list',
            'trainings'  => $query->orderByDesc('date_start')->get(),
            'areas'      => Training::whereNotNull('area')->distinct()->orderBy('area')->pluck('area'),
            'trainers'   => $this->activeTrainers(),
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

        $documents = Document::with('uploader')
            ->where('training_id', $training->id)
            ->orderByDesc('created_at')
            ->get();

        return view('ec.trainings', [
            'activePage'       => 'trainings',
            'mode'             => 'detail',
            'viewTraining'     => $training,
            'viewParticipants' => $participants,
            'viewDocs'         => $documents,
            'trainers'         => $this->activeTrainers(),
            'progress'         => $this->progress($training),
        ]);
    }

    /**
     * Budget/timeline percentages and the derived "health" pill shown on
     * the detail view's Project Progress card.
     */
    private function progress(Training $training): array
    {
        $budgetAlloc = (float) ($training->budget_allocated ?? 0);
        $budgetUsed = (float) ($training->budget_used ?? 0);
        $budgetPct = $budgetAlloc > 0 ? min(100, round($budgetUsed / $budgetAlloc * 100, 1)) : null;
        $budgetRemain = $budgetAlloc > 0 ? $budgetAlloc - $budgetUsed : null;

        $dateStart = $training->date_start;
        $dateEnd = $training->date_end;
        $today = now()->startOfDay();
        $timePct = null;
        $daysLeft = null;
        $totalDays = null;

        if ($dateStart && $dateEnd) {
            $totalDays = max(1, $dateStart->diffInDays($dateEnd));
            $elapsed = $today->lt($dateStart) ? 0 : ($today->gt($dateEnd) ? $totalDays : $dateStart->diffInDays($today));
            $timePct = min(100, round($elapsed / $totalDays * 100, 1));
            $daysLeft = $today->gt($dateEnd) ? 0 : $today->diffInDays($dateEnd);
        }

        [$healthLabel, $healthHex] = $this->health($budgetPct, $timePct);

        return compact('budgetAlloc', 'budgetUsed', 'budgetPct', 'budgetRemain', 'dateStart', 'dateEnd', 'timePct', 'daysLeft', 'totalDays', 'healthLabel', 'healthHex');
    }

    private function health(?float $budgetPct, ?float $timePct): array
    {
        if ($budgetPct === null && $timePct === null) {
            return ['No data', 'var(--gray-300)'];
        }

        $b = $budgetPct ?? 0;
        $t = $timePct ?? 0;

        if ($b >= 100 || $b > $t + 20) {
            return ['Over Budget / Behind', '#EF4444'];
        }
        if ($b > $t + 10 || ($t >= 90 && $b > 80)) {
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

    private function trainingRules(): array
    {
        return [
            'title'               => 'required|string|max:200',
            'area'                => 'required|string|max:120',
            'description'         => 'nullable|string',
            'date_start'          => 'nullable|date',
            'date_end'            => 'nullable|date',
            'budget_allocated'    => 'nullable|numeric|min:0',
            'budget_used'         => 'nullable|numeric|min:0',
            'status'              => ['nullable', Rule::in(['Proposed', 'Approved', 'Ongoing', 'Completed'])],
            'target_participants' => 'nullable|integer|min:1',
            'trainer_id'          => 'nullable|exists:users,id',
        ];
    }

    private function create(Request $request)
    {
        $data = Validator::make($request->all(), $this->trainingRules())->validate();

        Training::create([
            'title'                => $data['title'],
            'area'                 => $data['area'],
            'description'          => $data['description'] ?? null,
            'date_start'           => $data['date_start'] ?? null,
            'date_end'             => $data['date_end'] ?? null,
            'status'               => $data['status'] ?? 'Proposed',
            'trainer_id'           => $data['trainer_id'] ?? null,
            'target_participants'  => $data['target_participants'] ?? 0,
            'budget_allocated'     => $data['budget_allocated'] ?? null,
            'budget_used'          => $data['budget_used'] ?? 0,
            'created_by'           => Auth::guard('web')->id(),
        ]);

        return redirect()->route('ec.dashboard')->with('success', 'Training created.');
    }

    private function update(Request $request)
    {
        $data = Validator::make($request->all(), $this->trainingRules() + [
            'training_id' => 'required|integer|exists:trainings,id',
        ])->validate();

        Training::where('id', $data['training_id'])->update([
            'title'                => $data['title'],
            'area'                 => $data['area'],
            'description'          => $data['description'] ?? null,
            'date_start'           => $data['date_start'] ?? null,
            'date_end'             => $data['date_end'] ?? null,
            'status'               => $data['status'] ?? 'Proposed',
            'trainer_id'           => $data['trainer_id'] ?? null,
            'target_participants'  => $data['target_participants'] ?? 0,
            'budget_allocated'     => $data['budget_allocated'] ?? null,
            'budget_used'          => $data['budget_used'] ?? 0,
        ]);

        return redirect()->route('ec.trainings', ['view' => $data['training_id']])->with('success', 'Training updated.');
    }

    private function delete(Request $request)
    {
        Training::where('id', (int) $request->input('training_id'))->delete();

        return redirect()->route('ec.trainings')->with('success', 'Training deleted.');
    }

    private function updateStatus(Request $request)
    {
        $data = Validator::make($request->all(), [
            'training_id' => 'required|exists:trainings,id',
            'status'      => ['required', Rule::in(['Proposed', 'Approved', 'Ongoing', 'Completed'])],
        ])->validate();

        Training::where('id', $data['training_id'])->update(['status' => $data['status']]);

        return back()->with('success', 'Training status updated.');
    }

    private function updateBudget(Request $request)
    {
        $data = Validator::make($request->all(), [
            'training_id'      => 'required|integer|exists:trainings,id',
            'budget_allocated' => 'nullable|numeric|min:0',
            'budget_used'      => 'nullable|numeric|min:0',
        ])->validate();

        Training::where('id', $data['training_id'])->update([
            'budget_allocated' => $data['budget_allocated'] ?? null,
            'budget_used'      => $data['budget_used'] ?? 0,
        ]);

        return redirect()->route('ec.trainings', ['view' => $data['training_id']])->with('success', 'Budget updated successfully.');
    }
}
