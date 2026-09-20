<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * "Trainings" tab of Manage Public Site Content — the EC either picks an
 * existing Activity (App\Models\Training, created the normal way under
 * Manage Trainings / Ec\TrainingController) or quick-adds a brand new one,
 * then uploads a photo for it and it becomes "featured": a draft selection
 * visible to her immediately here, but only shown on the public site (the
 * landing page's Courses Offered grid and the public Trainings page) once
 * she clicks "Publish All Changes" (see Ec\PublishController::publishAll(),
 * Training::hasUnpublishedFeatureChanges()/publishFeature()).
 *
 * Replaces the old TrainingCategory-based Trainings tab (see
 * Ec\TrainingCategoryController) — that model/table/controller are left in
 * place but no longer wired into any route or view for this purpose.
 *
 * Images use the same public-disk convention as every other page-content
 * image (storage/app/public, symlinked to public/storage) — these are
 * rendered on fully logged-out public pages, so they must be reachable
 * without a session.
 */
class FeaturedActivityController extends Controller
{
    private array $allowedImageTypes = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private int   $maxImageSize      = 5 * 1024 * 1024; // 5 MB, same ceiling as every other page-content image

    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'feature_existing' => $this->featureExisting($request),
            'feature_new'      => $this->featureNew($request),
            'update_image'     => $this->updateImage($request),
            'unfeature'        => $this->unfeature($request),
            default            => $this->toTab($request),
        };
    }

    /**
     * Redirects back to the Manage Public Site Content page with the
     * Trainings tab (or whichever tab the submitting form said it was on)
     * re-selected, instead of back()/route() always landing on Pages.
     */
    private function toTab(Request $request)
    {
        return redirect()->route('ec.page-content', ['tab' => $request->input('tab', 'trainings')]);
    }

    /** Feature an Activity that already exists in the system. */
    private function featureExisting(Request $request)
    {
        $data = Validator::make($request->all(), [
            'training_id' => 'required|integer|exists:trainings,id',
        ])->validate();

        $training = Training::findOrFail($data['training_id']);

        if ($request->hasFile('featured_image')) {
            [$ok, $result] = $this->storeImage($request, $training);
            if (! $ok) {
                return $this->toTab($request)->with('error', $result);
            }
            $training->featured_image = $result;
        }

        $training->is_featured = true;
        $training->save();

        return $this->toTab($request)->with('success', 'Activity featured as draft. Click "Publish All Changes" to make it live.');
    }

    /**
     * Quick-add a brand new Activity and feature it in one step. Only
     * requires what Ec\TrainingController::create()'s validation truly
     * requires at minimum (title, area, program_id) plus an optional
     * description — everything else (dates, budget, team, etc.) is left
     * for the full Activities flow to fill in later if needed. Area is a
     * plain text field: EC types whatever specialization she wants, it is
     * not limited to a fixed list.
     */
    private function featureNew(Request $request)
    {
        $data = Validator::make($request->all(), [
            'title'       => 'required|string|max:200',
            'area'        => 'required|string|max:120',
            'description' => 'nullable|string',
            'program_id'  => 'required|integer|exists:programs,id',
        ])->validate();

        $training = Training::create([
            'title'                => $data['title'],
            'area'                 => $data['area'],
            'description'          => $data['description'] ?? null,
            'program_id'           => $data['program_id'],
            'target_participants'  => 0,
            'created_by'           => Auth::guard('web')->id(),
        ]);

        if ($request->hasFile('featured_image')) {
            [$ok, $result] = $this->storeImage($request, $training);
            if (! $ok) {
                return $this->toTab($request)->with('error', $result);
            }
            $training->featured_image = $result;
        }

        $training->is_featured = true;
        $training->save();

        return $this->toTab($request)->with('success', 'New activity created and featured as draft. Click "Publish All Changes" to make it live.');
    }

    private function updateImage(Request $request)
    {
        $training = Training::find((int) $request->input('id'));
        if (! $training) {
            return $this->toTab($request)->with('error', 'Activity not found.');
        }

        if (! $request->hasFile('featured_image')) {
            return $this->toTab($request)->with('error', 'Please choose an image to upload.');
        }

        [$ok, $result] = $this->storeImage($request, $training);
        if (! $ok) {
            return $this->toTab($request)->with('error', $result);
        }

        $training->featured_image = $result;
        $training->save();

        return $this->toTab($request)->with('success', 'Image saved as draft. Click "Publish All Changes" to make it live.');
    }

    /**
     * Removes the Activity from the featured (draft) list. Reversible and
     * non-destructive — it only clears the draft is_featured flag, it never
     * deletes the underlying Training/Activity itself (that's what the
     * Activities section is for). The public site keeps showing it until
     * "Publish All Changes" pushes this unfeature live.
     */
    private function unfeature(Request $request)
    {
        $training = Training::find((int) $request->input('id'));
        if ($training) {
            $training->is_featured = false;
            $training->save();
        }

        return $this->toTab($request)->with('success', 'Activity removed from the featured list.');
    }

    /**
     * @return array{0: bool, 1: string} [success, publicUrlOrErrorMessage]
     */
    private function storeImage(Request $request, Training $training): array
    {
        $validator = Validator::make($request->all(), [
            'featured_image' => 'required|file|mimes:jpg,jpeg,png,webp,gif',
        ]);

        if ($validator->fails()) {
            return [false, $validator->errors()->first('featured_image')];
        }

        $file = $request->file('featured_image');
        $ext  = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, $this->allowedImageTypes, true)) {
            return [false, 'Only JPG, PNG, WEBP, and GIF images are allowed.'];
        }

        if ($file->getSize() > $this->maxImageSize) {
            return [false, 'Image must be smaller than 5 MB.'];
        }

        $filename = 'featured-activity_' . $training->id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $file->storeAs('page-content', $filename, 'public');

        // Only delete the old file if it isn't the one still live on the
        // public site (published_featured_image) — draft edits must never
        // break what's currently published. The old draft file (if
        // orphaned) is cleaned up once publishAll() supersedes
        // published_featured_image instead.
        if ($training->featured_image && $training->featured_image !== $training->published_featured_image) {
            $this->deleteStoredImageUrl($training->featured_image);
        }

        return [true, asset('storage/page-content/' . $filename)];
    }

    private function deleteStoredImageUrl(string $url): void
    {
        $marker = '/storage/page-content/';
        $pos    = strpos($url, $marker);
        if ($pos === false) {
            return;
        }

        $path = 'page-content/' . substr($url, $pos + strlen($marker));
        Storage::disk('public')->delete($path);
    }
}
