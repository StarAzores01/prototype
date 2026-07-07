<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExtensionCoordinatorParticipantController extends Controller
{
    /**
     * List participants enrolled in the given training.
     */
    public function index(Training $training): View
    {
        $training->load('participants.user');

        return view('ec.trainings.participants.index', [
            'training' => $training,
        ]);
    }

    /**
     * Show the form to add a beneficiary as a participant.
     */
    public function create(Training $training): View
    {
        return view('ec.trainings.participants.create', [
            'training' => $training,
            'availableBeneficiaries' => $this->availableBeneficiaries($training),
        ]);
    }

    /**
     * Enroll a beneficiary as a participant of the training.
     */
    public function store(Request $request, Training $training): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'beneficiary')),
                Rule::unique('participants')->where(fn ($query) => $query->where('training_id', $training->id)),
            ],
        ], [
            'user_id.unique' => 'This beneficiary is already enrolled in this training.',
        ]);

        $training->participants()->create([
            'user_id' => $validated['user_id'],
            'status' => 'enrolled',
        ]);

        return redirect()
            ->route('extension-coordinator.trainings.participants.index', $training)
            ->with('status', 'Participant added successfully.');
    }

    /**
     * Show the form to change a participant's status.
     */
    public function edit(Training $training, Participant $participant): View
    {
        $this->ensureParticipantBelongsToTraining($training, $participant);

        return view('ec.trainings.participants.edit', [
            'training' => $training,
            'participant' => $participant,
        ]);
    }

    /**
     * Update a participant's status.
     */
    public function update(Request $request, Training $training, Participant $participant): RedirectResponse
    {
        $this->ensureParticipantBelongsToTraining($training, $participant);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['enrolled', 'completed', 'dropped'])],
        ]);

        $participant->update($validated);

        return redirect()
            ->route('extension-coordinator.trainings.participants.index', $training)
            ->with('status', 'Participant status updated.');
    }

    /**
     * Remove a participant from the training.
     */
    public function destroy(Training $training, Participant $participant): RedirectResponse
    {
        $this->ensureParticipantBelongsToTraining($training, $participant);

        $participant->delete();

        return redirect()
            ->route('extension-coordinator.trainings.participants.index', $training)
            ->with('status', 'Participant removed.');
    }

    /**
     * Beneficiary users not yet enrolled in this training.
     */
    private function availableBeneficiaries(Training $training): Collection
    {
        return User::where('role', 'beneficiary')
            ->whereDoesntHave('participations', fn ($query) => $query->where('training_id', $training->id))
            ->orderBy('name')
            ->get();
    }

    /**
     * Guard against editing/updating/deleting a participant via the
     * wrong training's URL (the route has two independently bound
     * parameters, so this cross-check keeps them consistent).
     */
    private function ensureParticipantBelongsToTraining(Training $training, Participant $participant): void
    {
        abort_unless($participant->training_id === $training->id, 404);
    }
}
