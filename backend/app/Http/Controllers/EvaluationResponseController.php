<?php

namespace App\Http\Controllers;

use App\Models\EvaluationForm;
use App\Models\EvaluationResponse;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EvaluationResponseController extends Controller
{
    /**
     * List published evaluation forms for trainings the beneficiary
     * is enrolled in, marking which ones are already answered.
     */
    public function index(Request $request): View
    {
        $trainingIds = Participant::where('user_id', $request->user()->id)
            ->where('status', '!=', 'dropped')
            ->pluck('training_id');

        $forms = EvaluationForm::whereIn('training_id', $trainingIds)
            ->where('status', 'published')
            ->with('training')
            ->get();

        $answeredFormIds = EvaluationResponse::where('user_id', $request->user()->id)
            ->whereIn('evaluation_form_id', $forms->pluck('id'))
            ->pluck('evaluation_form_id')
            ->unique();

        return view('beneficiary.evaluation-forms.index', [
            'forms' => $forms,
            'answeredFormIds' => $answeredFormIds,
        ]);
    }

    /**
     * Show a published form's questions for the beneficiary to answer.
     */
    public function show(Request $request, EvaluationForm $evaluationForm): View
    {
        $this->authorizeAnswering($request, $evaluationForm);

        $evaluationForm->load('questions');

        return view('beneficiary.evaluation-forms.show', [
            'form' => $evaluationForm,
        ]);
    }

    /**
     * Save all answers for the form in one submission.
     */
    public function store(Request $request, EvaluationForm $evaluationForm): RedirectResponse
    {
        $this->authorizeAnswering($request, $evaluationForm);

        $evaluationForm->load('questions');

        $rules = ['answers' => ['required', 'array']];

        foreach ($evaluationForm->questions as $question) {
            $rules["answers.{$question->id}"] = match ($question->question_type) {
                'rating' => ['required', 'integer', 'between:1,5'],
                'yes_no' => ['required', Rule::in(['yes', 'no'])],
                default => ['required', 'string'],
            };
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($evaluationForm, $validated, $request) {
            foreach ($validated['answers'] as $questionId => $answer) {
                EvaluationResponse::create([
                    'evaluation_form_id' => $evaluationForm->id,
                    'evaluation_question_id' => $questionId,
                    'user_id' => $request->user()->id,
                    'response_text' => (string) $answer,
                ]);
            }
        });

        return redirect()
            ->route('beneficiary.evaluation-forms.index')
            ->with('status', 'Thank you! Your responses have been submitted.');
    }

    /**
     * A beneficiary may only answer a form if: it is published, they
     * are an active participant of its training, and they have not
     * already submitted a response to it.
     */
    private function authorizeAnswering(Request $request, EvaluationForm $evaluationForm): void
    {
        abort_unless($evaluationForm->status === 'published', 404);

        $isParticipant = Participant::where('training_id', $evaluationForm->training_id)
            ->where('user_id', $request->user()->id)
            ->where('status', '!=', 'dropped')
            ->exists();

        abort_unless($isParticipant, 403);

        $alreadyAnswered = EvaluationResponse::where('evaluation_form_id', $evaluationForm->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        abort_if($alreadyAnswered, 403, 'You have already submitted a response to this form.');
    }
}
