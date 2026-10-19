<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\Participant;
use App\Models\Training;
use App\Support\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $participants = $participantsQuery->get();

        // One row per person: enrollments that belong to the same account are
        // grouped (oldest first) so a participant in several activities shows
        // once with all of their activities listed. Unregistered participants
        // have no account yet, so each stays its own row.
        $groups = $participants
            ->sortBy('id')
            ->groupBy(fn ($p) => $p->beneficiary_id ? 'b'.$p->beneficiary_id : 'p'.$p->id)
            ->sortBy(fn ($rows) => mb_strtolower($rows->first()->full_name))
            ->values();

        return view('ec.participants', [
            'activePage'     => 'participants',
            'participants'   => $participants,
            'groups'         => $groups,
            'trainings'      => Training::orderBy('title')->get(['id', 'title']),
            // For the "Add Existing Account" picker: every active account,
            // plus which trainings each one is already enrolled in so the
            // picker can grey those out (the server re-checks regardless).
            'accounts'       => Beneficiary::where('is_active', true)
                ->orderBy('last_name')->orderBy('first_name')
                ->get(['id', 'username', 'first_name', 'last_name', 'email']),
            'enrolledMap'    => Participant::whereNotNull('beneficiary_id')
                ->get(['beneficiary_id', 'training_id'])
                ->groupBy('beneficiary_id')
                ->map(fn ($rows) => $rows->pluck('training_id')->values()),
            'q'              => $q,
            'filterTraining' => $filterTraining,
        ]);
    }

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'add'    => $this->add($request),
            'enroll_existing' => $this->enrollExisting($request),
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

    /**
     * Enroll an EXISTING beneficiary account into a training. Only creates a
     * new Participant (enrollment) row linked to the account — the account
     * itself is never modified; its details are copied into the enrollment
     * row, same shape as a normal registration. Refuses duplicates.
     */
    private function enrollExisting(Request $request)
    {
        $data = Validator::make($request->all(), [
            'beneficiary_id' => 'required|integer|exists:beneficiaries,id',
            'training_id'    => 'required|integer|exists:trainings,id',
        ])->validate();

        $result = DB::transaction(function () use ($data) {
            // Lock the account row so two simultaneous enrolls can't both
            // pass the duplicate check below.
            $account = Beneficiary::whereKey($data['beneficiary_id'])->lockForUpdate()->first();

            if (! $account || ! $account->is_active) {
                return ['error', 'That account is not available.'];
            }

            $already = Participant::where('beneficiary_id', $account->id)
                ->where('training_id', $data['training_id'])
                ->exists();

            if ($already) {
                return ['error', "{$account->full_name} is already enrolled in that activity."];
            }

            // An existing account keeps the User ID it was first registered
            // with. Only fall back to a new ID if no enrollment has one.
            $bfId = Participant::where('beneficiary_id', $account->id)
                ->whereNotNull('id_number')
                ->where('id_number', '!=', '')
                ->orderBy('id')
                ->value('id_number')
                ?? IdGenerator::next('BF', 'participants');

            // Normalize only this account; never infer identity from names.
            Participant::where('beneficiary_id', $account->id)->update(['id_number' => $bfId]);

            Participant::create([
                'full_name'      => $account->full_name,
                'id_number'      => $bfId,
                'beneficiary_id' => $account->id,
                'training_id'    => $data['training_id'],
                'phone'          => $account->phone,
                'address'        => $account->address,
                'age'            => $account->age,
                'sex'            => $account->sex,
                'barangay'       => $account->barangay,
                'municipality'   => $account->municipality,
            ]);

            return ['success', "{$account->full_name} enrolled using their existing account."];
        });

        return back()->with($result[0], $result[1]);
    }

    private function edit(Request $request)
    {
        $data = Validator::make($request->all(), $this->rules() + [
            'participant_id' => 'required|integer|exists:participants,id',
            'id_number'      => 'nullable|string|max:40',
        ])->validate();

        return DB::transaction(function () use ($data) {
            $participant = Participant::findOrFail($data['participant_id']);
            if ($participant->beneficiary_id) {
                Beneficiary::whereKey($participant->beneficiary_id)->lockForUpdate()->firstOrFail();
            }
            $participant = Participant::whereKey($participant->id)->lockForUpdate()->firstOrFail();

            if ($participant->beneficiary_id
                && Participant::where('beneficiary_id', $participant->beneficiary_id)
                    ->where('training_id', $data['training_id'])
                    ->where('id', '!=', $participant->id)->exists()) {
                return back()->with('error', 'That participant is already enrolled in the selected activity.');
            }

            $canonicalId = $participant->beneficiary_id
                ? Participant::where('beneficiary_id', $participant->beneficiary_id)
                    ->whereNotNull('id_number')->where('id_number', '!=', '')
                    ->orderBy('id')->value('id_number')
                : null;
            $personal = [
                'full_name' => $data['full_name'],
                'id_number' => $participant->beneficiary_id
                    ? ($canonicalId ?? IdGenerator::next('BF', 'participants'))
                    : ($data['id_number'] ?? ''),
                'phone' => $data['phone'] ?? null, 'address' => $data['address'] ?? null,
                'age' => $data['age'] ?? null, 'sex' => $data['sex'] ?? null,
            ];

            // Account details and canonical ID are shared; activity actions
            // still address the individual enrollment row.
            if ($participant->beneficiary_id) {
                Participant::where('beneficiary_id', $participant->beneficiary_id)->update($personal);
            }
            $participant->update($personal + ['training_id' => $data['training_id']]);

            return back()->with('success', 'Participant updated.');
        });
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
