<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Shared avatar-upload logic for all four role profile controllers
 * (Ec, Trainer, Evaluator, Beneficiary).
 *
 * The uploaded file is stored on the "local" disk (storage/app/private/uploads)
 * — same as every other upload in this app — so it is never directly accessible
 * via a public URL. It is served through FileDownloadController@avatar (see
 * routes/web.php) which checks that the requester owns the avatar or is an EC.
 *
 * Allowed formats: jpg, jpeg, png, webp, gif  — validated by both the Laravel
 * mimes rule (content-sniffs the bytes) and an extension check.
 * Maximum size: 2 MB — avatars are small; reject anything larger.
 */
trait HandlesAvatarUpload
{
    private array $allowedAvatarTypes = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private int   $maxAvatarSize      = 2 * 1024 * 1024; // 2 MB

    /**
     * Validates the uploaded avatar, stores it, removes the old one, and
     * updates the $model's `avatar` column.
     *
     * @return array{0: bool, 1: ?string}  [success, errorMessage]
     */
    protected function storeAvatar(Request $request, Model $model): array
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|file|mimes:jpg,jpeg,png,webp,gif',
        ]);

        if ($validator->fails()) {
            return [false, $validator->errors()->first('avatar')];
        }

        $file = $request->file('avatar');
        $ext  = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, $this->allowedAvatarTypes, true)) {
            return [false, 'Only JPG, PNG, WEBP, and GIF images are allowed.'];
        }

        if ($file->getSize() > $this->maxAvatarSize) {
            return [false, 'Avatar image must be smaller than 2 MB.'];
        }

        // Delete old avatar if one exists
        $old = $model->avatar;
        if ($old) {
            Storage::disk('local')->delete('uploads/' . $old);
        }

        // Store new avatar with a cryptographically random filename
        $stored = 'avatar_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $file->storeAs('uploads', $stored, 'local');

        $model->update(['avatar' => $stored]);

        return [true, null];
    }
}
