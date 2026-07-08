<?php

namespace App\Http\Controllers;

use App\Models\EvaluationForm;
use App\Models\Training;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationFormController extends Controller
{
    /**
     * List evaluation forms for the training.
     */
    public function index(Request $request, Training $training): View
    {
        $this->authorizeTrainingAccess($request, $training);

        $training->load('evaluationForms');

        return view('evaluation-forms.index', [
            'training' => $training,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    /**
     * Show the form to create a new evaluation form.
     */
    public function create(Request $request, Training $training): View
    {
        $this->authorizeTrainingAccess($request, $training);

        return view('evaluation-forms.create', [
            'training' => $training,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    /**
     * Create a new (draft) evaluation form for the training.
     */
    public function store(Request $request, Training $training): RedirectResponse
    {
        $this->authorizeTrainingAccess($request, $training);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $form = $training->evaluationForms()->create([
            'title' => $validated['title'],
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);

        return redirect()
            ->route($this->routePrefix($request).'.evaluation-forms.show', [$training, $form])
            ->with('status', 'Evaluation form created. Add some questions, then publish it.');
    }

    /**
     * Show a form's questions, the add-question form, and submitted
     * responses so far.
     */
    public function show(Request $request, Training $training, EvaluationForm $evaluationForm): View
    {
        $this->authorizeTrainingAccess($request, $training);
        $this->ensureFormBelongsToTraining($training, $evaluationForm);

        $evaluationForm->load(['questions.responses.user']);

        return view('evaluation-forms.show', [
            'training' => $training,
            'form' => $evaluationForm,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    /**
     * Publish a draft form so beneficiaries can see and answer it.
     */
    public function publish(Request $request, Training $training, EvaluationForm $evaluationForm): RedirectResponse
    {
        $this->authorizeTrainingAccess($request, $training);
        $this->ensureFormBelongsToTraining($training, $evaluationForm);

        $evaluationForm->update(['status' => 'published']);

        return redirect()
            ->route($this->routePrefix($request).'.evaluation-forms.show', [$training, $evaluationForm])
            ->with('status', 'Evaluation form published. Beneficiaries can now answer it.');
    }

    /**
     * Extension Coordinators may manage evaluation forms for any
     * training. Project Leaders may only manage forms for trainings
     * they are assigned to.
     */
    private function authorizeTrainingAccess(Request $request, Training $training): void
    {
        if ($request->user()->role === 'project_leader') {
            abort_unless($training->project_leader_id === $request->user()->id, 403);
        }
    }

    /**
     * Guard against viewing/publishing a form via the wrong training's
     * URL.
     */
    private function ensureFormBelongsToTraining(Training $training, EvaluationForm $evaluationForm): void
    {
        abort_unless($evaluationForm->training_id === $training->id, 404);
    }

    /**
     * Route name prefix differs by role since EC and PL have separate
     * URL namespaces for the same underlying evaluation form feature.
     */
    private function routePrefix(Request $request): string
    {
        return $request->user()->role === 'project_leader'
            ? 'project-leader.trainings'
            : 'extension-coordinator.trainings';
    }
}
