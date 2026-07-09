<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BeneficiaryTrainingController extends Controller
{
    /**
     * Read-only list of trainings the beneficiary is enrolled in.
     * Enrollment itself is managed by the Extension Coordinator.
     */
    public function index(Request $request): View
    {
        $participations = Participant::where('user_id', $request->user()->id)
            ->with('training.projectLeader')
            ->latest()
            ->get();

        return view('beneficiary.trainings.index', [
            'participations' => $participations,
        ]);
    }
}
