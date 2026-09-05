<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Shared by Ec\TrainingController (always allowed) and
 * Trainer\TrainingController (lead-only — checked by the caller before this
 * runs). Validates + stores an Activity's cover/display picture, replacing
 * whichever one was there before.
 */
trait HandlesCoverImageUpload
{
    private array $allowedCoverImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    private int $maxCoverImageSize = 5 * 1024 * 1024; // 5 MB — a display picture, not a document.

    /** @return array{0: bool, 1: ?string} [success, errorMessage] */
    private function storeCoverImage(Request $request, Training $training): array
    {
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
