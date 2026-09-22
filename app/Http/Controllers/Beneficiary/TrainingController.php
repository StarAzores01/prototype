<?php

namespace App\Http\Controllers\Beneficiary;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Participant;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingController extends Controller
{
    public function index(Request $request)
    {
        $beneficiaryId = Auth::guard('beneficiary')->id();
        $viewId = (int) $request->query('view', 0);

        if ($viewId) {
            $enrolled = Participant::where('training_id', $viewId)->where('beneficiary_id', $beneficiaryId)->exists();

            if (! $enrolled) {
                return redirect()->route('beneficiary.trainings')->with('error', 'You are not enrolled in this activity.');
            }

            $training = Training::with(['trainer', 'program'])->find($viewId);

            if ($training) {
                return $this->detailView($training);
            }
            // No match: original falls through to the list view instead of a 404.
        }

        return $this->listView($request, $beneficiaryId);
    }

    private function detailView(Training $training)
    {
        // 'ec_trainer' visibility means EC + Project Leader/Trainer only  -
        // FileDownloadController::authorizeVisibility() already denies a
        // beneficiary who tries to open one (403). Listing them here too
        // used to show a beneficiary a document card they'd get blocked
        // from the moment they clicked it, so only 'public'  -  the one
        // tier beneficiaries are actually allowed to open  -  is shown.
        $documents = Document::where('training_id', $training->id)
            ->where('visibility', 'public')
            ->orderByDesc('created_at')
            ->get();

        return view('beneficiary.trainings', [
            'activePage'   => 'trainings',
            'mode'         => 'detail',
            'viewTraining' => $training,
            'viewDocs'     => $documents,
        ]);
    }

    private function listView(Request $request, int $beneficiaryId)
    {
        $q = trim($request->query('q', ''));
        $status = $request->query('status', '');

        $trainingIds = Participant::where('beneficiary_id', $beneficiaryId)->pluck('training_id')->unique();

        $query = Training::with(['trainer', 'program'])
            ->whereIn('id', $trainingIds)
            ->withCount('participants as enrolled');

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")->orWhere('area', 'like', "%{$q}%");
            });
        }
        if ($status) {
            $query->where('status', $status);
        }

        return view('beneficiary.trainings', [
            'activePage' => 'trainings',
            'mode'       => 'list',
            'trainings'  => $query->orderByDesc('date_start')->get(),
            'q'          => $q,
            'status'     => $status,
        ]);
    }
}
