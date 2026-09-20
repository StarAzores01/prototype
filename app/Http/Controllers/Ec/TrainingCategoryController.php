<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\TrainingCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * "Trainings" tab of Manage Public Site Content — the training category
 * cards (photo/name/description) shown on the landing page's Courses
 * Offered grid and behind the public Trainings page's listing cards. See
 * App\Models\TrainingCategory.
 *
 * Images use the same public-disk convention as Ec\PageContentController's
 * images (storage/app/public, symlinked to public/storage) — these are
 * rendered on fully logged-out public pages, so they must be reachable
 * without a session, same reasoning as every other page-content image.
 */
class TrainingCategoryController extends Controller
{
    private array $allowedImageTypes = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private int   $maxImageSize      = 5 * 1024 * 1024; // 5 MB, same ceiling as every other page-content image

    /**
     * No dedicated index/view — the Trainings tab is rendered by
     * Ec\PageContentController::index() alongside Pages/Site Settings on
     * the same combined page. This controller only handles the row-level
     * write actions, all redirecting back() to that same page.
     */
    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'add'          => $this->add($request),
            'update'       => $this->updateCategory($request),
            'change_image' => $this->changeImage($request),
            'delete'       => $this->delete($request),
            default        => $this->toTab($request),
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

    private function add(Request $request)
    {
        $data = Validator::make($request->all(), [
            'activity_name' => 'required|string|max:100|unique:training_categories,activity_name',
            'project_name'  => 'nullable|string|max:150',
            'description'   => 'nullable|string',
            'status'        => ['required', Rule::in(['active', 'draft'])],
        ])->validate();

        $category = TrainingCategory::create($data);

        if ($request->hasFile('image')) {
            [$ok, $result] = $this->storeImage($request, $category);
            if (! $ok) {
                return $this->toTab($request)->with('error', $result);
            }
            $category->update(['image' => $result]);
        }

        return $this->toTab($request)->with('success', 'Training added.');
    }

    private function updateCategory(Request $request)
    {
        $data = Validator::make($request->all(), [
            'id'             => 'required|integer|exists:training_categories,id',
            'activity_name'  => 'required|string|max:100|unique:training_categories,activity_name,' . $request->input('id'),
            'project_name'   => 'nullable|string|max:150',
            'description'    => 'nullable|string',
            'status'         => ['required', Rule::in(['active', 'draft'])],
        ])->validate();

        TrainingCategory::where('id', $data['id'])->update([
            'activity_name' => $data['activity_name'],
            'project_name'  => $data['project_name'] ?? null,
            'description'   => $data['description'] ?? null,
            'status'        => $data['status'],
        ]);

        return $this->toTab($request)->with('success', 'Training saved as draft. Click "Publish All Changes" to make it live.');
    }

    private function changeImage(Request $request)
    {
        $category = TrainingCategory::find((int) $request->input('id'));
        if (! $category) {
            return $this->toTab($request)->with('error', 'Training not found.');
        }

        if (! $request->hasFile('image')) {
            return $this->toTab($request)->with('error', 'Please choose an image to upload.');
        }

        [$ok, $result] = $this->storeImage($request, $category);
        if (! $ok) {
            return $this->toTab($request)->with('error', $result);
        }

        $category->update(['image' => $result]);

        return $this->toTab($request)->with('success', 'Image saved as draft. Click "Publish All Changes" to make it live.');
    }

    private function delete(Request $request)
    {
        $category = TrainingCategory::find((int) $request->input('id'));
        if ($category) {
            if ($category->image) {
                $this->deleteStoredImageUrl($category->image);
            }
            $category->delete();
        }

        return $this->toTab($request)->with('success', 'Training deleted.');
    }

    /**
     * @return array{0: bool, 1: string} [success, publicUrlOrErrorMessage]
     */
    private function storeImage(Request $request, TrainingCategory $category): array
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpg,jpeg,png,webp,gif',
        ]);

        if ($validator->fails()) {
            return [false, $validator->errors()->first('image')];
        }

        $file = $request->file('image');
        $ext  = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, $this->allowedImageTypes, true)) {
            return [false, 'Only JPG, PNG, WEBP, and GIF images are allowed.'];
        }

        if ($file->getSize() > $this->maxImageSize) {
            return [false, 'Image must be smaller than 5 MB.'];
        }

        $filename = 'training-category_' . $category->id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $file->storeAs('page-content', $filename, 'public');

        // Only delete the old file if it isn't the one still live on the
        // public site (published_image) — draft edits must never break
        // what's currently published. The old draft file (if orphaned) is
        // cleaned up once publishAll() supersedes published_image instead.
        if ($category->image && $category->image !== $category->published_image) {
            $this->deleteStoredImageUrl($category->image);
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
