<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Extension Coordinator self-registration — no whitelist gate, matching the
 * original ecsignuppage.php (unlike trainer/evaluator, which require a
 * pre-approved whitelist entry). The original had a bug where id_number was
 * read into $old for display but never actually captured/validated/saved,
 * so the form could never successfully submit; fixed here per user request
 * while keeping registration open (no admin gate).
 */
class RegisteredEcController extends Controller
{
    private array $positions = ['Professor', 'Assistant Professor', 'Instructor'];

    public function create()
    {
        return view('auth.ec-signup', ['positions' => $this->positions]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'first_name' => 'required|string|max:80',
            'last_name'  => 'required|string|max:80',
            'username'   => 'required|string|max:60',
            'email'      => 'required|email|max:120',
            'position'   => ['required', Rule::in($this->positions)],
            'id_number'  => 'required|string|max:40',
            'password'   => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[\W_]/'],
            'password2'  => 'required|same:password',
            'agree'      => 'required',
        ])->validate();

        if (User::where('email', $data['email'])->orWhere('username', $data['username'])->orWhere('id_number', $data['id_number'])->exists()) {
            return back()->withErrors([
                'email' => 'An account with this email, username, or ID number already exists.',
            ])->withInput();
        }

        User::create([
            'username'      => $data['username'],
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'id_number'     => $data['id_number'],
            'position'      => $data['position'],
            'role'          => 'extension_coordinator',
            'password_hash' => Hash::make($data['password']),
            'is_active'     => true,
        ]);

        return redirect()->route('login')->with('success', 'Account created! Please log in.');
    }
}
