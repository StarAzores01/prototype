<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Shared by Ec\TrainingController (always allowed) and
 * Trainer\TrainingController (lead-only — checked by the caller before this
 * runs). Validates + stores an Activity's cover/display picture, replacing
 * whichever one was there before.
 *
 * Security model:
 *   • The EC routes are protected by the 'role:extension_coordinator' middleware
 *     before the request ever reaches this trait.
 *   • The Trainer routes are protected by 'role:trainer' middleware AND by the
 *     caller's isLeadUser() check before invoking this trait.
 *   • This trait adds a backend role check as a final defence-in-depth layer:
 *     only extension_coordinator and trainer roles may invoke it.
 *     Any other authenticated role (evaluator, beneficiary) that somehow POSTs
 *     an upload_cover action to a trainer-prefixed route — which is already
 *     impossible due to the route middleware — gets a 403 here regardless.
 */
trait HandlesCoverImageUpload
{
    private array $allowedCoverImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    private int $maxCoverImageSize = 5 * 1024 * 1024; // 5 MB — a display picture, not a document.

    /** @return array{0: bool, 1: ?string} [success, errorMessage] */
    private function storeCoverImage(Request $request, Training $training): array
    {
        // Defence-in-depth role guard — the route middleware already blocks anyone
        // who isn't an extension_coordinator or trainer, but we double-check here
        // so this trait cannot be misused if ever mixed into another controller.
        $role = Auth::guard('web')->check() ? Auth::guard('web')->user()->role : null;
        if (! in_array($role, ['extension_coordinator', 'trainer'], true)) {
            abort(403, 'Only Extension Coordinators and Project Leaders may change cover images.');
        }

        $validator = Validator::make($request->all(), [
            // mimes: content-sniffs the actual bytes (via fileinfo), not just the
            // claimed filename extension — a renamed .txt/.php can't pass this.
            'cover_image' => 'required|file|mimes:jpg,jpeg,png,gif,webp',
        ]);
        if ($validator->fails()) {
            return [false, $validator->errors()->first()];
        }

        $file = $request->file('cover_image');
        $ext = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, $this->allowedCoverImageTypes, true)) {
            return [false, 'Only JPG, PNG, GIF, and WEBP images are allowed.'];
        }
        if ($file->getSize() > $this->maxCoverImageSize) {
            return [false, 'Image exceeds 5 MB limit.'];
        }

        $oldCover = $training->cover_image;

        $stored = 'cover_'.bin2hex(random_bytes(8)).'.'.$ext;
        $file->storeAs('uploads', $stored, 'local');

        $training->update(['cover_image' => $stored]);

        if ($oldCover) {
            Storage::disk('local')->delete('uploads/'.$oldCover);
        }

        return [true, null];
    }
}
