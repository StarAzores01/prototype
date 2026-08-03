<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RegisteredBeneficiaryController extends Controller
{
    public function create()
    {
        return view('auth.beneficiary-signup');
    }

    /** Open self-registration — no whitelist gate, unlike trainer/evaluator. */
    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'first_name'  => 'required|string|max:80',
            'last_name'   => 'required|string|max:80',
            'username'    => 'required|string|max:60|unique:beneficiaries,username',
            'email'       => 'required|email|max:120|unique:beneficiaries,email',
            'phone'       => 'nullable|string|max:30',
            'address'     => 'nullable|string|max:255',
            'age'         => 'nullable|integer|min:1|max:120',
            'sex'         => ['nullable', Rule::in(['Male', 'Female', 'Other'])],
            'password'    => 'required|string|min:8',
            'password2'   => 'required|same:password',
        ])->validate();

        Beneficiary::create([
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

        return redirect()->route('login')->with('success', 'Account created! You can now log in.');
    }
}
