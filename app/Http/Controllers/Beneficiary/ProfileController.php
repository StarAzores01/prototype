<?php

namespace App\Http\Controllers\Beneficiary;

use App\Http\Controllers\Concerns\HandlesAvatarUpload;
use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    use HandlesAvatarUpload;

    public function show()
    {
        $beneficiary = Auth::guard('beneficiary')->user();
        $participant = Participant::where('beneficiary_id', $beneficiary->id)->first();

        return view('beneficiary.profile', [
            'activePage'  => 'profile',
            'beneficiary' => $beneficiary,
            'participant' => $participant,
            'initials'    => strtoupper(mb_substr($beneficiary->first_name ?? '', 0, 1).mb_substr($beneficiary->last_name ?? '', 0, 1)),
        ]);
    }

    public function update(Request $request)
    {
        $beneficiary = Auth::guard('beneficiary')->user();
        $action      = $request->input('action');

        if ($action === 'upload_avatar') {
            [$ok, $err] = $this->storeAvatar($request, $beneficiary);
            return $ok
                ? redirect()->route('beneficiary.profile')->with('success', 'Profile picture updated.')
                : redirect()->route('beneficiary.profile')->with('error', $err ?? 'Upload failed.');
        }

        if ($action === 'update_profile') {
            $data = Validator::make($request->all(), [
                'first_name' => 'required|string|max:80',
                'last_name'  => 'required|string|max:80',
                'email'      => 'required|email|max:120',
                'phone'      => 'nullable|string|max:30',
                'address'    => 'nullable|string|max:255',
                'age'        => 'nullable|integer|min:1|max:255',
                'sex'        => 'nullable|in:Male,Female,Other',
            ])->validate();

            if (Beneficiary::where('email', $data['email'])->where('id', '!=', $beneficiary->id)->exists()) {
                return redirect()->route('beneficiary.profile')->with('error', 'Email already taken.');
            }

            $beneficiary->update([
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'email'      => $data['email'],
                'phone'      => $data['phone'] ?? null,
                'address'    => $data['address'] ?? null,
                'age'        => $data['age'] ?? null,
                'sex'        => $data['sex'] ?? null,
            ]);

            return redirect()->route('beneficiary.profile')->with('success', 'Profile updated.');
        }

        if ($action === 'change_password') {
            $data = $request->validate([
                'current_password' => 'required|string',
                'new_password'     => 'required|string',
                'confirm_password' => 'required|string',
            ]);

            if (! Hash::check($data['current_password'], $beneficiary->password_hash)) {
                return redirect()->route('beneficiary.profile')->with('error', 'Current password is incorrect.');
            }
            if ($data['new_password'] !== $data['confirm_password']) {
                return redirect()->route('beneficiary.profile')->with('error', 'New passwords do not match.');
            }
            if (strlen($data['new_password']) < 8) {
                return redirect()->route('beneficiary.profile')->with('error', 'Password must be at least 8 characters.');
            }

            $beneficiary->update(['password_hash' => Hash::make($data['new_password'])]);

            return redirect()->route('beneficiary.profile')->with('success', 'Password changed.');
        }

        return redirect()->route('beneficiary.profile');
    }
}
