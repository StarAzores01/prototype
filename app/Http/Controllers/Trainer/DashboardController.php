<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Training;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $trainerId = Auth::guard('web')->id();

        $activeTrainings = Training::visibleToTrainer($trainerId)
            ->whereIn('status', ['Ongoing', 'Approved', 'Proposed'])
            ->count();

        $totalTrainees = Participant::whereHas('training', function ($query) use ($trainerId) {
            $query->visibleToTrainer($trainerId);
        })->count();

        $docsUploaded = Document::where('uploaded_by', $trainerId)->count();

        $myTrainings = Training::with('program')
            ->withCount('participants as enrolled')
            ->visibleToTrainer($trainerId)
            ->orderByRaw("CASE status WHEN 'Ongoing' THEN 0 WHEN 'Proposed' THEN 1 WHEN 'Completed' THEN 2 ELSE 3 END")
            ->orderBy('date_start')
            ->get();

        $myPrograms = Program::visibleToTrainer($trainerId)
            ->with(['lead'])
            ->withCount('trainings as activity_count')
            ->orderByRaw("CASE status WHEN 'Ongoing' THEN 0 WHEN 'Proposed' THEN 1 WHEN 'Completed' THEN 2 ELSE 3 END")
            ->orderBy('timeline_start')
            ->get();

        $latestDocs = Document::with('training')
            ->where(function ($query) use ($trainerId) {
                $query->where('uploaded_by', $trainerId)
                    ->orWhereIn('visibility', ['public', 'ec_trainer']);
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('trainer.dashboard', [
            'activePage'       => 'dashboard',
            'userFirstName'    => Auth::guard('web')->user()->first_name,
            'activeTrainings'  => $activeTrainings,
            'totalTrainees'    => $totalTrainees,
            'docsUploaded'     => $docsUploaded,
            'myTrainings'      => $myTrainings,
            'myPrograms'       => $myPrograms,
            'latestDocs'       => $latestDocs,
        ]);
    }
}
