<?php

namespace App\Http\Controllers\Evaluator;

use App\Http\Controllers\Controller;
use App\Models\ImpactAssessment;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $evaluatorId = Auth::guard('web')->id();

        $base = ImpactAssessment::where('evaluator_id', $evaluatorId);

        return view('evaluator.dashboard', [
            'activePage'      => 'dashboard',
            'userFirstName'   => Auth::guard('web')->user()->first_name,
            'totalSubmitted'  => (clone $base)->count(),
            'totalSent'       => (clone $base)->whereNotNull('participant_form_id')->count(),
            'recent'          => ImpactAssessment::with('training.program')
                ->where('evaluator_id', $evaluatorId)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
