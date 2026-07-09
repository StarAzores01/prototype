<?php

namespace App\Http\Controllers;

use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectLeaderParticipantController extends Controller
{
    /**
     * Read-only list of participants enrolled in the training. Project
     * Leaders may view but not add/edit/remove - only Extension
     * Coordinators manage enrollment.
     */
    public function index(Request $request, Training $training): View
    {
        abort_unless($training->project_leader_id === $request->user()->id, 403);

        $training->load('participants.user');

        return view('project-leader.trainings.participants.index', [
            'training' => $training,
        ]);
    }
}
