<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function show()
    {
        $ecUser = Auth::guard('web')->user();

        return view('ec.profile', [
            'activePage' => 'profile',
            'ecUser'     => $ecUser,
            'initials'   => strtoupper(mb_substr($ecUser->first_name, 0, 1) . mb_substr($ecUser->last_name, 0, 1)),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::guard('web')->user();
        $action = $request->input('action');

        if ($action === 'update_profile') {
            $data = Validator::make($request->all(), [
                'first_name' => 'required|string|max:80',
                'last_name'  => 'required|string|max:80',
                'email'      => 'required|email|max:120',
                'username'   => 'required|string|max:60',
            ])->validate();

            if (\App\Models\User::where('username', $data['username'])->where('id', '!=', $user->id)->exists()) {
                return back()->with('error', 'That username is already taken.');
            }

            $user->update($data);

            return back()->with('success', 'Profile updated successfully.');
        }

        if ($action === 'change_password') {
            $data = $request->validate([
                'current_password' => 'required|string',
                'new_password'     => 'required|string',
                'confirm_password' => 'required|string',
            ]);

            if (! Hash::check($data['current_password'], $user->password_hash)) {
                return back()->with('error', 'Current password is incorrect.');
            }
            if ($data['new_password'] !== $data['confirm_password']) {
                return back()->with('error', 'New passwords do not match.');
            }
            if (strlen($data['new_password']) < 6) {
                return back()->with('error', 'Password must be at least 6 characters.');
            }

            $user->update(['password_hash' => Hash::make($data['new_password'])]);

            return back()->with('success', 'Password changed successfully.');
        }

        return back();
    }
}
