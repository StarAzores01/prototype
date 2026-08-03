<?php

namespace App\Http\Controllers\Beneficiary;

use App\Http\Controllers\Controller;
use App\Models\EvalForm;
use App\Models\Notification;
use App\Models\Participant;
use App\Models\SkillsForm;
use App\Models\Training;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        $beneficiaryId = Auth::guard('beneficiary')->id();

        $trainingIds = Participant::where('beneficiary_id', $beneficiaryId)->pluck('training_id')->unique();

        $completedTrainings = Training::whereIn('id', $trainingIds)->where('status', 'Completed')->count();

        $pendingEvals = EvalForm::whereIn('training_id', $trainingIds)
            ->whereNotNull('sent_at')
            ->whereDoesntHave('responses', fn ($q) => $q->where('beneficiary_id', $beneficiaryId))
            ->count();

        $pendingSkills = SkillsForm::whereIn('training_id', $trainingIds)
            ->whereNotNull('sent_at')
            ->whereDoesntHave('responses', fn ($q) => $q->where('beneficiary_id', $beneficiaryId))
            ->count();

        $recentTrainings = Training::whereIn('id', $trainingIds)
            ->with('trainer')
            ->orderByDesc('date_start')
            ->limit(3)
            ->get();

        $notifications = Notification::where('is_read', false)
            ->where('role', 'beneficiary')
            ->where('user_id', $beneficiaryId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('beneficiary.home', [
            'activePage'          => 'home',
            'userFirstName'       => Auth::guard('beneficiary')->user()->first_name,
            'totalTrainings'      => $trainingIds->count(),
            'completedTrainings'  => $completedTrainings,
            'pendingEvals'        => $pendingEvals,
            'pendingSkills'       => $pendingSkills,
            'recentTrainings'     => $recentTrainings,
            'notifications'       => $notifications,
        ]);
    }
}
