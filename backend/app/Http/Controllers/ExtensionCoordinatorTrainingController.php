<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExtensionCoordinatorTrainingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $trainings = Training::with(['creator', 'projectLeader'])
            ->latest()
            ->get();

        return view('ec.trainings.index', [
            'trainings' => $trainings,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('ec.trainings.create', [
            'projectLeaders' => $this->projectLeaders(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTraining($request);

        $validated['created_by'] = $request->user()->id;

        Training::create($validated);

        return redirect()
            ->route('extension-coordinator.trainings.index')
            ->with('status', 'Training created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Training $training): View
    {
        $training->load(['creator', 'projectLeader', 'participants.user']);

        return view('ec.trainings.show', [
            'training' => $training,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Training $training): View
    {
        return view('ec.trainings.edit', [
            'training' => $training,
            'projectLeaders' => $this->projectLeaders(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Training $training): RedirectResponse
    {
        $validated = $this->validateTraining($request);

        $training->update($validated);

        return redirect()
            ->route('extension-coordinator.trainings.show', $training)
            ->with('status', 'Training updated successfully.');
    }

    /**
     * Cancel the specified resource (soft cancel, not a hard delete, so
     * related participants/attendance/documents/evaluations are kept).
     */
    public function destroy(Training $training): RedirectResponse
    {
        $training->update(['status' => 'cancelled']);

        return redirect()
            ->route('extension-coordinator.trainings.index')
            ->with('status', 'Training cancelled.');
    }

    /**
     * Shared validation rules for store and update.
     *
     * @return array<string, mixed>
     */
    private function validateTraining(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'ongoing', 'completed', 'cancelled'])],
            'project_leader_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'project_leader')),
            ],
        ]);
    }

    /**
     * Users eligible to be assigned as a training's Project Leader.
     */
    private function projectLeaders(): Collection
    {
        return User::where('role', 'project_leader')->orderBy('name')->get();
    }
}
