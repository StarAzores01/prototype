<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegisteredBeneficiaryController extends Controller
{
    public function create()
    {
        return view('auth.beneficiary-signup');
    }

    /**
     * Matches an existing, not-yet-claimed Participant record by name + ID
     * number — same whitelist-style gate as trainer/evaluator, mirroring the
     * original beneficiary-signup.php. A beneficiary must first have been
     * enrolled in a training by the EC before they can create a login.
     */
    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'first_name'  => 'required|string|max:80',
            'last_name'   => 'required|string|max:80',
            'username'    => 'required|string|max:60',
            'id_number'   => 'required|string|max:40',
            'email'       => 'required|email|max:120',
            'phone'       => 'nullable|string|max:30',
            'address'     => 'nullable|string|max:255',
            'age'         => 'nullable|integer|min:1|max:120',
            'sex'         => ['nullable', Rule::in(['Male', 'Female', 'Other'])],
            'password'    => 'required|string|min:8',
            'password2'   => 'required|same:password',
        ])->validate();

        $fullName = trim($data['first_name'].' '.$data['last_name']);

        $participant = Participant::whereRaw('LOWER(full_name) = LOWER(?)', [$fullName])
            ->where('id_number', $data['id_number'])
            ->whereNull('beneficiary_id')
            ->first();

        if (! $participant) {
            throw ValidationException::withMessages([
                'id_number' => 'No matching participant record found, or an account already exists for this ID. Please contact the Extension Coordinator.',
            ]);
        }

        if (Beneficiary::where('email', $data['email'])->orWhere('username', $data['username'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An account with this email or username already exists.',
            ]);
        }

        $beneficiary = Beneficiary::create([
            'username'      => $data['username'],
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?? null,
            'address'       => $data['address'] ?? null,
            'age'           => $data['age'] ?? null,
            'sex'           => $data['sex'] ?? null,
            'password_hash' => Hash::make($data['password']),
            'is_active'     => true,
        ]);

        $participant->update(['beneficiary_id' => $beneficiary->id]);

        return redirect()->route('login')->with('success', 'Account created! You can now log in.');
    }
}
