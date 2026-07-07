<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Training;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Show the attendance-marking form for a session date, plus a
     * running summary of each participant's attendance so far.
     */
    public function index(Request $request, Training $training): View
    {
        $this->authorizeTrainingAccess($request, $training);

        $training->load('participants.user');

        $sessionDate = $request->query('session_date', now()->toDateString());

        $existingStatuses = Attendance::where('training_id', $training->id)
            ->where('session_date', $sessionDate)
            ->pluck('status', 'participant_id');

        $summary = Attendance::where('training_id', $training->id)
            ->selectRaw('participant_id, status, count(*) as total')
            ->groupBy('participant_id', 'status')
            ->get()
            ->groupBy('participant_id');

        return view('attendance.index', [
            'training' => $training,
            'sessionDate' => $sessionDate,
            'existingStatuses' => $existingStatuses,
            'summary' => $summary,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    /**
     * Save attendance for every participant for one session date.
     * Re-submitting the same date updates the existing rows instead
     * of creating duplicates (see the unique DB constraint).
     */
    public function store(Request $request, Training $training): RedirectResponse
    {
        $this->authorizeTrainingAccess($request, $training);

        $validated = $request->validate([
            'session_date' => ['required', 'date'],
            'statuses' => ['required', 'array'],
            'statuses.*' => ['required', Rule::in(['present', 'absent', 'late', 'excused'])],
        ]);

        foreach ($validated['statuses'] as $participantId => $status) {
            $participant = $training->participants()->find($participantId);

            if (! $participant) {
                continue;
            }

            Attendance::updateOrCreate(
                [
                    'training_id' => $training->id,
                    'participant_id' => $participant->id,
                    'session_date' => $validated['session_date'],
                ],
                [
                    'status' => $status,
                    'recorded_by' => $request->user()->id,
                ]
            );
        }

        return redirect()
            ->route($this->routePrefix($request).'.attendance.index', [
                'training' => $training,
                'session_date' => $validated['session_date'],
            ])
            ->with('status', 'Attendance saved for '.$validated['session_date'].'.');
    }

    /**
     * Extension Coordinators may record attendance for any training.
     * Project Leaders may only record attendance for trainings they
     * are assigned to.
     */
    private function authorizeTrainingAccess(Request $request, Training $training): void
    {
        if ($request->user()->role === 'project_leader') {
            abort_unless($training->project_leader_id === $request->user()->id, 403);
        }
    }

    /**
     * Route name prefix differs by role since EC and PL have separate
     * URL namespaces for the same underlying attendance feature.
     */
    private function routePrefix(Request $request): string
    {
        return $request->user()->role === 'project_leader'
            ? 'project-leader.trainings'
            : 'extension-coordinator.trainings';
    }
}
