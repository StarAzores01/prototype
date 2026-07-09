<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectLeaderTrainingController extends Controller
{
    /**
     * List trainings this Project Leader is assigned to.
     */
    public function index(Request $request): View
    {
        $trainings = $request->user()->trainingsLed()
            ->withCount('participants')
            ->latest()
            ->get();

        return view('project-leader.trainings.index', [
            'trainings' => $trainings,
        ]);
    }
}
