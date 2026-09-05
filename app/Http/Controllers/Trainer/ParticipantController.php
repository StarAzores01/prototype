<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParticipantController extends Controller
{
    public function index(Request $request)
    {
        $trainerId = Auth::guard('web')->id();

        $q = trim($request->query('q', ''));
        $filterTraining = (int) $request->query('training', 0);

        $query = Participant::with(['training', 'evaluations'])
            ->whereHas('training', fn ($t) => $t->visibleToTrainer($trainerId));

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('full_name', 'like', "%{$q}%")
                    ->orWhere('id_number', 'like', "%{$q}%");
            });
        }
        if ($filterTraining) {
            $query->where('training_id', $filterTraining);
        }

        return view('trainer.participants', [
            'activePage'   => 'participants',
            'participants' => $query->orderBy('full_name')->get(),
            'myTrainings'  => Training::visibleToTrainer($trainerId)->orderBy('title')->get(['id', 'title']),
            'q'            => $q,
            'filterT'      => $filterTraining,
        ]);
    }
}
