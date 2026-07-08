<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ImpactAssessmentController extends Controller
{
    /**
     * List impact assessments requested for the training's participants.
     */
    public function index(Request $request, Training $training): View
    {
        $this->authorizeTrainingAccess($request, $training);

        $training->load('impactAssessments.user');

        return view('impact-assessments.index', [
            'training' => $training,
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    /**
     * Show the form to request an impact assessment from a participant.
     */
    public function create(Request $request, Training $training): View
    {
        $this->authorizeTrainingAccess($request, $training);

        return view('impact-assessments.create', [
            'training' => $training,
            'availableBeneficiaries' => $this->availableBeneficiaries($training),
            'routePrefix' => $this->routePrefix($request),
        ]);
    }

    /**
     * Create a pending impact assessment for a participant. The
     * beneficiary fills in the actual notes later.
     */
    public function store(Request $request, Training $training): RedirectResponse
    {
        $this->authorizeTrainingAccess($request, $training);

        $validated = $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'beneficiary')),
                Rule::unique('impact_assessments')->where(fn ($query) => $query->where('training_id', $training->id)),
            ],
        ], [
            'user_id.unique' => 'An impact assessment has already been requested from this beneficiary for this training.',
        ]);

        $training->impactAssessments()->create([
            'user_id' => $validated['user_id'],
            'status' => 'pending',
        ]);

        return redirect()
            ->route($this->routePrefix($request).'.impact-assessments.index', $training)
            ->with('status', 'Impact assessment requested.');
    }

    /**
     * Extension Coordinators may manage impact assessments for any
     * training. Project Leaders may only manage assessments for
     * trainings they are assigned to.
     */
    private function authorizeTrainingAccess(Request $request, Training $training): void
    {
        if ($request->user()->role === 'project_leader') {
            abort_unless($training->project_leader_id === $request->user()->id, 403);
        }
    }

    /**
     * Participants (beneficiaries) of this training who do not yet
     * have an impact assessment requested from them.
     */
    private function availableBeneficiaries(Training $training): Collection
    {
        return User::where('role', 'beneficiary')
            ->whereHas('participations', fn ($query) => $query->where('training_id', $training->id))
            ->whereDoesntHave('impactAssessments', fn ($query) => $query->where('training_id', $training->id))
            ->orderBy('name')
            ->get();
    }

    /**
     * Route name prefix differs by role since EC and PL have separate
     * URL namespaces for the same underlying impact assessment feature.
     */
    private function routePrefix(Request $request): string
    {
        return $request->user()->role === 'project_leader'
            ? 'project-leader.trainings'
            : 'extension-coordinator.trainings';
    }
}
