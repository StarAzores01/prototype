<?php

namespace App\Http\Controllers;

use App\Models\EvaluationForm;
use App\Models\Training;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EvaluationQuestionController extends Controller
{
    /**
     * Add a question to a draft evaluation form. Questions cannot be
     * added once the form is published, since existing respondents
     * would never see the new question.
     */
    public function store(Request $request, Training $training, EvaluationForm $evaluationForm): RedirectResponse
    {
        $this->authorizeTrainingAccess($request, $training);
        $this->ensureFormBelongsToTraining($training, $evaluationForm);

        if ($evaluationForm->status !== 'draft') {
            return redirect()
                ->route($this->routePrefix($request).'.evaluation-forms.show', [$training, $evaluationForm])
                ->with('error', 'Cannot add questions to a published form.');
        }

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'question_type' => ['required', Rule::in(['rating', 'text', 'yes_no'])],
        ]);

        $evaluationForm->questions()->create([
            'question_text' => $validated['question_text'],
            'question_type' => $validated['question_type'],
            'order' => $evaluationForm->questions()->count() + 1,
        ]);

        return redirect()
            ->route($this->routePrefix($request).'.evaluation-forms.show', [$training, $evaluationForm])
            ->with('status', 'Question added.');
    }

    /**
     * Extension Coordinators may manage questions for any training's
     * forms. Project Leaders may only manage forms for trainings they
     * are assigned to.
     */
    private function authorizeTrainingAccess(Request $request, Training $training): void
    {
        if ($request->user()->role === 'project_leader') {
            abort_unless($training->project_leader_id === $request->user()->id, 403);
        }
    }

    private function ensureFormBelongsToTraining(Training $training, EvaluationForm $evaluationForm): void
    {
        abort_unless($evaluationForm->training_id === $training->id, 404);
    }

    private function routePrefix(Request $request): string
    {
        return $request->user()->role === 'project_leader'
            ? 'project-leader.trainings'
            : 'extension-coordinator.trainings';
    }
}
