<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Concerns\HandlesAvatarUpload;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    use HandlesAvatarUpload;

    public function show()
    {
        $user = Auth::guard('web')->user();

        return view('trainer.profile', [
            'activePage' => 'profile',
            'trainer'    => $user,
            'initials'   => strtoupper(mb_substr($user->first_name ?? '', 0, 1).mb_substr($user->last_name ?? '', 0, 1)),
        ]);
    }

    public function update(Request $request)
    {
        $user   = Auth::guard('web')->user();
        $action = $request->input('action');

        if ($action === 'upload_avatar') {
            [$ok, $err] = $this->storeAvatar($request, $user);
            return $ok
                ? redirect()->route('trainer.profile')->with('success', 'Profile picture updated.')
                : redirect()->route('trainer.profile')->with('error', $err ?? 'Upload failed.');
        }

        if ($action === 'update_profile') {
            $data = Validator::make($request->all(), [
                'first_name' => 'required|string|max:80',
                'last_name'  => 'required|string|max:80',
                'email'      => 'required|email|max:120',
                'username'   => 'required|string|max:60',
            ])->validate();

            if (User::where('username', $data['username'])->where('id', '!=', $user->id)->exists()) {
                return redirect()->route('trainer.profile')->with('error', 'Username already taken.');
            }
            if (User::where('email', $data['email'])->where('id', '!=', $user->id)->exists()) {
                return redirect()->route('trainer.profile')->with('error', 'Email already taken.');
            }

            $user->update($data);

            return redirect()->route('trainer.profile')->with('success', 'Profile updated.');
        }

        if ($action === 'change_password') {
            $data = $request->validate([
                'current_password' => 'required|string',
                'new_password'     => 'required|string',
                'confirm_password' => 'required|string',
            ]);

            if (! Hash::check($data['current_password'], $user->password_hash)) {
                return redirect()->route('trainer.profile')->with('error', 'Current password incorrect.');
            }
            if ($data['new_password'] !== $data['confirm_password']) {
                return redirect()->route('trainer.profile')->with('error', 'Passwords do not match.');
            }
            if (strlen($data['new_password']) < 6) {
                return redirect()->route('trainer.profile')->with('error', 'Minimum 6 characters.');
            }

            $user->update(['password_hash' => Hash::make($data['new_password'])]);

            return redirect()->route('trainer.profile')->with('success', 'Password updated.');
        }

        return redirect()->route('trainer.profile');
    }
}
