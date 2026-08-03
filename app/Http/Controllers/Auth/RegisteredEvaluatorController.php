<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EvaluatorWhitelist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegisteredEvaluatorController extends Controller
{
    public function create()
    {
        return view('auth.evaluator-signup');
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'first_name' => 'required|string|max:80',
            'last_name'  => 'required|string|max:80',
            'username'   => 'required|string|max:60',
            'email'      => 'required|email|max:120',
            'position'   => ['required', Rule::in(['Professor', 'Assistant Professor', 'Instructor'])],
            'id_number'  => 'required|string|max:40',
            'password'   => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[\W_]/'],
            'password2'  => 'required|same:password',
            'agree'      => 'required',
        ])->validate();

        // Match by name + ID against the EC's pre-approved evaluator whitelist.
        $whitelisted = EvaluatorWhitelist::whereRaw('LOWER(first_name) = LOWER(?)', [$data['first_name']])
            ->whereRaw('LOWER(last_name) = LOWER(?)', [$data['last_name']])
            ->where('id_number', $data['id_number'])
            ->where('is_registered', false)
            ->first();

        if (! $whitelisted) {
            throw ValidationException::withMessages([
                'general' => 'No matching approved evaluator found for the name and ID you entered, or the account has already been registered. Please contact the Extension Coordinator.',
            ]);
        }

        if (User::where('email', $data['email'])->orWhere('username', $data['username'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An account with this email or username already exists.',
            ]);
        }

        User::create([
            'username'      => $data['username'],
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'id_number'     => $whitelisted->id_number,
            'position'      => $data['position'],
            'role'          => 'evaluator',
            'password_hash' => Hash::make($data['password']),
            'is_active'     => true,
        ]);

        $whitelisted->update(['is_registered' => true]);

        return redirect()->route('login')->with('success', 'Evaluator account created! Your ID is ' . $whitelisted->id_number . '. You can now log in.');
    }
}
