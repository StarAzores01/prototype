<?php

namespace App\Http\Controllers\Beneficiary;

use App\Http\Controllers\Controller;
use App\Models\ImpactAssessmentForm;
use App\Models\ImpactAssessmentResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ImpactAssessmentController extends Controller
{
    public function index(Request $request)
    {
        $beneficiaryId = Auth::guard('beneficiary')->id();

        $forms = ImpactAssessmentForm::with('training')
            ->whereHas('training.participants', fn ($q) => $q->where('beneficiary_id', $beneficiaryId))
            ->whereNotNull('sent_at')
            ->orderByDesc('sent_at')
            ->get();

        foreach ($forms as $form) {
            $form->myResponse = ImpactAssessmentResponse::where('form_id', $form->id)
                ->where('beneficiary_id', $beneficiaryId)
                ->first();
        }

        $viewFormId = (int) $request->query('form', 0);
        $viewForm = $viewFormId ? $forms->firstWhere('id', $viewFormId) : null;

        return view('beneficiary.impact_assessment', [
            'activePage' => 'impact_assessment',
            'forms'      => $forms,
            'viewForm'   => $viewForm,
        ]);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'submit' => $this->submit($request),
            default  => back(),
        };
    }

    private function submit(Request $request)
    {
        $beneficiaryId = Auth::guard('beneficiary')->id();

        $data = Validator::make($request->all(), [
            'form_id'     => 'required|integer|exists:impact_assessment_forms,id',
            'training_id' => 'required|integer|exists:trainings,id',
        ])->validate();

        $formId = $data['form_id'];
        $trainingId = $data['training_id'];
        $answers = $request->input('answer', []);

        $already = ImpactAssessmentResponse::where('form_id', $formId)->where('beneficiary_id', $beneficiaryId)->exists();

        if ($already) {
            return redirect()->route('beneficiary.impact_assessment')
                ->with('error', 'You have already submitted this impact assessment and it can no longer be edited.');
        }

        ImpactAssessmentResponse::create([
            'form_id'       => $formId,
            'training_id'   => $trainingId,
            'beneficiary_id' => $beneficiaryId,
            'responses'     => $answers,
        ]);

        Notification::where('user_id', $beneficiaryId)
            ->where('training_id', $trainingId)
            ->where('role', 'beneficiary')
            ->update(['is_read' => true]);

        return redirect()->route('beneficiary.impact_assessment')->with('success', 'Impact assessment submitted. Thank you!');
    }
}
