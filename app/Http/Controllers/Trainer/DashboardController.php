<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Participant;
use App\Models\Training;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $trainerId = Auth::guard('web')->id();

        $activeTrainings = Training::where('trainer_id', $trainerId)
            ->whereIn('status', ['Ongoing', 'Approved', 'Proposed'])
            ->count();

        $totalTrainees = Participant::whereHas('training', function ($query) use ($trainerId) {
            $query->where('trainer_id', $trainerId);
        })->count();

        $docsUploaded = Document::where('uploaded_by', $trainerId)->count();

        $myTrainings = Training::withCount('participants as enrolled')
            ->where('trainer_id', $trainerId)
            ->orderByDesc('date_start')
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
            'latestDocs'       => $latestDocs,
        ]);
    }
}
