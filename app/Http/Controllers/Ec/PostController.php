<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * "Posts" tab of Manage Public Site Content — announcements EC creates,
 * edits, publishes/unpublishes and deletes, shown on the public landing
 * page's Posts & Announcements section. See App\Models\Post.
 *
 * Images use the same public-disk convention as Ec\PageContentController's
 * and Ec\TrainingCategoryController's images (storage/app/public, symlinked
 * to public/storage) — these are rendered on the fully logged-out public
 * landing page, so they must be reachable without a session.
 */
class PostController extends Controller
{
    private array $allowedImageTypes = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private int   $maxImageSize      = 5 * 1024 * 1024; // 5 MB, same ceiling as every other page-content image

    /**
     * No dedicated index/view — the Posts tab is rendered by
     * Ec\PageContentController::index() alongside Pages/Trainings/Site
     * Settings on the same combined page. This controller only handles the
     * row-level write actions, all redirecting back() to that same page.
     */
    public function store(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'add'       => $this->add($request),
            'update'    => $this->updatePost($request),
            'publish'   => $this->setStatus($request, 'published'),
            'unpublish' => $this->setStatus($request, 'draft'),
            'delete'    => $this->delete($request),
            default     => $this->toTab($request),
        };
    }

    /**
     * Redirects back to the Manage Public Site Content page with the
     * Posts tab (or whichever tab the submitting form said it was on)
     * re-selected, instead of back()/route() always landing on Pages.
     */
    private function toTab(Request $request)
    {
        return redirect()->route('ec.page-content', ['tab' => $request->input('tab', 'posts')]);
    }

    private function add(Request $request)
    {
        $data = Validator::make($request->all(), [
            'title'       => 'required|string|max:150',
            'description' => 'required|string',
            'status'      => ['required', Rule::in(['draft', 'published'])],
        ])->validate();

        $post = Post::create([
            'title'        => $data['title'],
            'description'  => $data['description'],
            'status'       => $data['status'],
            'author_id'    => Auth::guard('web')->id(),
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        if ($request->hasFile('image')) {
            [$ok, $result] = $this->storeImage($request, $post);
            if (! $ok) {
                return $this->toTab($request)->with('error', $result);
            }
            $post->update(['image' => $result]);
        }

        return $this->toTab($request)->with('success', $data['status'] === 'published' ? 'Post published.' : 'Post saved as draft.');
    }

    private function updatePost(Request $request)
    {
        $data = Validator::make($request->all(), [
            'id'          => 'required|integer|exists:posts,id',
            'title'       => 'required|string|max:150',
            'description' => 'required|string',
            'status'      => ['required', Rule::in(['draft', 'published'])],
        ])->validate();

        $post = Post::findOrFail($data['id']);

        $wasPublished = $post->isPublished();
        $post->title       = $data['title'];
        $post->description = $data['description'];
        $post->status       = $data['status'];
        if (! $wasPublished && $data['status'] === 'published') {
            $post->published_at = now();
        } elseif ($data['status'] === 'draft') {
            $post->published_at = null;
        }
        $post->save();

        if ($request->hasFile('image')) {
            [$ok, $result] = $this->storeImage($request, $post);
            if (! $ok) {
                return $this->toTab($request)->with('error', $result);
            }
            $post->update(['image' => $result]);
        }

        return $this->toTab($request)->with('success', 'Post updated.');
    }

    private function setStatus(Request $request, string $status)
    {
        $post = Post::find((int) $request->input('id'));
        if (! $post) {
            return $this->toTab($request)->with('error', 'Post not found.');
        }

        $post->status = $status;
        $post->published_at = $status === 'published' ? ($post->published_at ?? now()) : null;
        $post->save();

        return $this->toTab($request)->with('success', $status === 'published' ? 'Post published.' : 'Post unpublished.');
    }

    private function delete(Request $request)
    {
        $post = Post::find((int) $request->input('id'));
        if ($post) {
            if ($post->image) {
                $this->deleteStoredImageUrl($post->image);
            }
            $post->delete();
        }

        return $this->toTab($request)->with('success', 'Post deleted.');
    }

    /**
     * @return array{0: bool, 1: string} [success, publicUrlOrErrorMessage]
     */
    private function storeImage(Request $request, Post $post): array
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

        $filename = 'post_' . $post->id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $file->storeAs('page-content', $filename, 'public');

        if ($post->image) {
            $this->deleteStoredImageUrl($post->image);
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
