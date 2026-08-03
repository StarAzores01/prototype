<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Training;
use App\Support\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParticipantController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));
        $filterTraining = (int) $request->query('training', 0);

        $participantsQuery = Participant::with(['training:id,title', 'beneficiary:id,username', 'evaluations'])
            ->orderBy('full_name');

        if ($q) {
            $participantsQuery->where(function ($query) use ($q) {
                $query->where('full_name', 'like', "%{$q}%")
                    ->orWhere('id_number', 'like', "%{$q}%");
            });
        }
        if ($filterTraining) {
            $participantsQuery->where('training_id', $filterTraining);
        }

        return view('ec.participants', [
            'activePage'     => 'participants',
            'participants'   => $participantsQuery->get(),
            'trainings'      => Training::orderBy('title')->get(['id', 'title']),
            'q'              => $q,
            'filterTraining' => $filterTraining,
        ]);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'add'    => $this->add($request),
            'edit'   => $this->edit($request),
            'delete' => $this->delete($request),
            'toggle' => $this->toggle($request),
            default  => back(),
        };
    }

    private function rules(): array
    {
        return [
            'full_name'   => 'required|string|max:160',
            'phone'       => 'nullable|string|max:30',
            'address'     => 'nullable|string|max:255',
            'age'         => 'nullable|integer|min:1|max:120',
            'sex'         => 'nullable|in:Male,Female,Other',
            'training_id' => 'required|integer|exists:trainings,id',
        ];
    }

    private function add(Request $request)
    {
        $data = Validator::make($request->all(), $this->rules())->validate();

        $bfId = IdGenerator::next('BF', 'participants');

        Participant::create([
            'full_name'   => $data['full_name'],
            'id_number'   => $bfId,
            'phone'       => $data['phone'] ?? null,
            'address'     => $data['address'] ?? null,
            'age'         => $data['age'] ?? null,
            'sex'         => $data['sex'] ?? null,
            'training_id' => $data['training_id'],
        ]);

        return back()->with('success', "Participant registered. Assigned ID: {$bfId}");
    }

    private function edit(Request $request)
    {
        $data = Validator::make($request->all(), $this->rules() + [
            'participant_id' => 'required|integer|exists:participants,id',
            'id_number'      => 'nullable|string|max:40',
        ])->validate();

        Participant::where('id', $data['participant_id'])->update([
            'full_name'   => $data['full_name'],
            'id_number'   => $data['id_number'] ?? '',
            'phone'       => $data['phone'] ?? null,
            'address'     => $data['address'] ?? null,
            'age'         => $data['age'] ?? null,
            'sex'         => $data['sex'] ?? null,
            'training_id' => $data['training_id'],
        ]);

        return back()->with('success', 'Participant updated.');
    }

    private function delete(Request $request)
    {
        Participant::where('id', (int) $request->input('participant_id'))->delete();

        return back()->with('success', 'Participant removed.');
    }

    private function toggle(Request $request)
    {
        $participant = Participant::find((int) $request->input('participant_id'));

        if ($participant) {
            $participant->update(['is_active' => ! $participant->is_active]);
        }

        return back()->with('success', 'Participant access updated.');
    }
}
