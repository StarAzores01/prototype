<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Participant;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $trainerId = Auth::guard('web')->id();

        $myTrainings = Training::visibleToTrainer($trainerId)
            ->orderByDesc('date_start')
            ->get(['id', 'title']);

        $selectedTraining = (int) $request->query('training', $myTrainings->first()?->id ?? 0);

        $trainingInfo = null;
        $participants = collect();
        $days = collect();
        $attGrid = [];

        if ($selectedTraining) {
            $trainingInfo = Training::where('id', $selectedTraining)
                ->visibleToTrainer($trainerId)
                ->first();

            if ($trainingInfo) {
                $participants = Participant::where('training_id', $selectedTraining)
                    ->orderBy('full_name')
                    ->get();

                // pluck() still applies the model's 'date' cast to session_date,
                // yielding Carbon instances — flatten to plain 'Y-m-d' strings so
                // they can be used as array keys / hidden-field values below,
                // matching the original's raw PDO::FETCH_COLUMN string values.
                $days = Attendance::where('training_id', $selectedTraining)
                    ->distinct()
                    ->orderBy('session_date')
                    ->pluck('session_date')
                    ->map(fn ($d) => $d->format('Y-m-d'));

                if ($days->isNotEmpty()) {
                    $rows = Attendance::where('training_id', $selectedTraining)
                        ->get(['participant_id', 'session_date', 'status']);

                    foreach ($rows as $r) {
                        $attGrid[$r->session_date->format('Y-m-d')][$r->participant_id] = $r->status;
                    }
                }
            }
        }

        return view('trainer.attendance', [
            'activePage'       => 'attendance',
            'myTrainings'      => $myTrainings,
            'selectedTraining' => $selectedTraining,
            'trainingInfo'     => $trainingInfo,
            'participants'     => $participants,
            'days'             => $days,
            'attGrid'          => $attGrid,
        ]);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'add_day' => $this->addDay($request),
            'save'    => $this->save($request),
            default   => back(),
        };
    }

    private function addDay(Request $request)
    {
        $trainerId = Auth::guard('web')->id();
        $trainingId = (int) $request->input('training_id');

        Validator::make($request->all(), [
            'new_date' => 'nullable|date',
        ])->validate();

        $newDate = $request->input('new_date');

        $training = Training::where('id', $trainingId)->visibleToTrainer($trainerId)->first();

        if ($training && $newDate) {
            $duplicate = Attendance::where('training_id', $trainingId)->where('session_date', $newDate)->exists();

            if (! $duplicate) {
                $participantIds = Participant::where('training_id', $trainingId)->pluck('id');

                if ($participantIds->isNotEmpty()) {
                    $rows = $participantIds->map(fn ($pid) => [
                        'training_id'    => $trainingId,
                        'participant_id' => $pid,
                        'session_date'   => $newDate,
                        'status'         => 'Absent',
                        'recorded_by'    => $trainerId,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ])->all();

                    DB::table('attendance')->insertOrIgnore($rows);
                }

                return redirect()->route('trainer.attendance', ['training' => $trainingId])->with('success', 'Day added.');
            }

            return redirect()->route('trainer.attendance', ['training' => $trainingId])->with('error', 'That date already exists.');
        }

        return redirect()->route('trainer.attendance', ['training' => $trainingId]);
    }

    private function save(Request $request)
    {
        $trainerId = Auth::guard('web')->id();
        $trainingId = (int) $request->input('training_id');
        $days = $request->input('days', []);
        $checked = $request->input('present', []);

        $training = Training::where('id', $trainingId)->visibleToTrainer($trainerId)->first();

        if ($training) {
            $participantIds = Participant::where('training_id', $trainingId)->pluck('id');

            $rows = [];
            foreach ($days as $di => $sessionDate) {
                if (! $sessionDate) {
                    continue;
                }
                foreach ($participantIds as $pid) {
                    $status = isset($checked[$di][$pid]) ? 'Present' : 'Absent';
                    $rows[] = [
                        'training_id'    => $trainingId,
                        'participant_id' => $pid,
                        'session_date'   => $sessionDate,
                        'status'         => $status,
                        'recorded_by'    => $trainerId,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }
            }

            if ($rows) {
                Attendance::upsert($rows, ['training_id', 'participant_id', 'session_date'], ['status', 'recorded_by', 'updated_at']);
            }

            return redirect()->route('trainer.attendance', ['training' => $trainingId])->with('success', 'Attendance saved.');
        }

        return redirect()->route('trainer.attendance', ['training' => $trainingId]);
    }
}
