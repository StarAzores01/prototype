<?php

namespace App\Http\Controllers\Beneficiary;

use App\Http\Controllers\Controller;
use App\Models\ImpactAssessmentForm;
use App\Models\ImpactAssessmentResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        abort_unless(Auth::guard('beneficiary')->user()?->is_active, 403);

        $data = Validator::make($request->all(), [
            'form_id' => 'required|integer',
        ])->validate();

        return DB::transaction(function () use ($request, $data, $beneficiaryId) {
            // Lock the form so concurrent submissions cannot both pass the
            // duplicate check. Authorization is repeated on every POST.
            $form = ImpactAssessmentForm::whereKey($data['form_id'])
                ->whereNotNull('sent_at')
                ->where('sent_at', '<=', now())
                ->whereHas('training.participants', fn ($q) => $q
                    ->where('beneficiary_id', $beneficiaryId)
                    ->where('is_active', true))
                ->lockForUpdate()
                ->first();

            abort_unless($form, 403);

            if ($form->responses()->where('beneficiary_id', $beneficiaryId)->exists()) {
                return redirect()->route('beneficiary.impact_assessment')
                    ->with('error', 'You have already submitted this impact assessment and it can no longer be edited.');
            }

            $answers = $this->validateAnswers($request, $form);

            ImpactAssessmentResponse::create([
                'form_id'        => $form->id,
                'training_id'    => $form->training_id,
                'beneficiary_id' => $beneficiaryId,
                'responses'      => $answers,
            ]);

            Notification::where('user_id', $beneficiaryId)
                ->where('training_id', $form->training_id)
                ->where('role', 'beneficiary')
                ->update(['is_read' => true]);

            return redirect()->route('beneficiary.impact_assessment')->with('success', 'Impact assessment submitted. Thank you!');
        });
    }

    private function validateAnswers(Request $request, ImpactAssessmentForm $form): array
    {
        $fields = $form->fields;
        if (! is_array($fields) || ! array_is_list($fields) || $fields === []) {
            throw ValidationException::withMessages(['answer' => 'This assessment has an invalid form definition.']);
        }

        $answers = $request->input('answer', []);
        Validator::make(['answer' => $answers], ['answer' => 'array'])->validate();

        // The Blade form uses numeric field indexes; reject alternate or
        // unknown identifiers instead of silently discarding extra answers.
        if (array_diff_key($answers, $fields)) {
            throw ValidationException::withMessages(['answer' => 'The submission contains an unknown question.']);
        }

        $rules = ['answer' => 'array'];
        foreach ($fields as $index => $field) {
            if (! is_array($field) || ! in_array($field['type'] ?? null, ['text', 'textarea', 'radio', 'select'], true)) {
                throw ValidationException::withMessages(['answer' => 'This assessment has an invalid question type.']);
            }

            $rules['answer.'.$index] = [! empty($field['required']) ? 'required' : 'nullable', 'string', 'max:5000'];
            if (in_array($field['type'], ['radio', 'select'], true)) {
                $options = $field['options'] ?? null;
                if (! is_array($options) || ! array_is_list($options) || $options === []
                    || count(array_filter($options, 'is_string')) !== count($options)) {
                    throw ValidationException::withMessages(['answer' => 'This assessment has invalid question choices.']);
                }
                $rules['answer.'.$index][] = Rule::in($options);
            }
        }

        return Validator::make(['answer' => $answers], $rules)->validate()['answer'];
    }
}
