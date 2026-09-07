<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\PageContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Lets EC edit the text/media content of the public-facing pages
 * (landing, about, contact, trainings-public, choose-role, privacy,
 * terms) without touching their forms, routes, or logic — those stay
 * hardcoded in the Blade views; only the section list in
 * config/page_content_sections.php + PageContent rows are editable here.
 *
 * Image uploads for this feature go on the "public" disk (storage/app/
 * public, symlinked to public/storage) — unlike every other upload in
 * this app (avatars, program/activity covers), which deliberately live
 * on the private "local" disk behind FileDownloadController's auth
 * check. Page content images are different on purpose: they're
 * rendered on pages a fully logged-out visitor sees, so they must be
 * reachable without a session at all.
 */
class PageContentController extends Controller
{
    private array $allowedImageTypes = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private int   $maxImageSize      = 5 * 1024 * 1024; // 5 MB, same ceiling as program/activity cover images

    public function index()
    {
        $pages = collect(config('page_content_sections'))->map(function (array $page, string $pageKey) {
            $sectionKeys = array_keys($page['sections']);
            $editedCount = PageContent::query()
                ->where('page_key', $pageKey)
                ->whereIn('section_key', $sectionKeys)
                ->whereNotNull('content_value')
                ->where('content_value', '!=', '')
                ->count();

            return [
                'page_key'      => $pageKey,
                'label'         => $page['label'],
                'total'         => count($sectionKeys),
                'edited'        => $editedCount,
            ];
        })->values();

        return view('ec.page-content.index', [
            'activePage' => 'page-content',
            'pages'      => $pages,
        ]);
    }

    public function edit(string $pageKey)
    {
        $page = config("page_content_sections.$pageKey");
        abort_if(! $page, 404);

        $existing = PageContent::query()
            ->where('page_key', $pageKey)
            ->whereIn('section_key', array_keys($page['sections']))
            ->get()
            ->keyBy('section_key');

        $sections = collect($page['sections'])->map(function (array $meta, string $sectionKey) use ($existing) {
            return $meta + [
                'key'   => $sectionKey,
                'value' => $existing->get($sectionKey)?->content_value,
            ];
        })->values();

        return view('ec.page-content.edit', [
            'activePage' => 'page-content',
            'pageKey'    => $pageKey,
            'pageLabel'  => $page['label'],
            'sections'   => $sections,
        ]);
    }

    public function update(Request $request, string $pageKey)
    {
        $page = config("page_content_sections.$pageKey");
        abort_if(! $page, 404);

        $userId = Auth::guard('web')->id();

        foreach ($page['sections'] as $sectionKey => $meta) {
            $inputName = str_replace('-', '_', $sectionKey);

            if ($meta['type'] === 'image') {
                if ($request->hasFile($inputName)) {
                    [$ok, $result] = $this->storeImage($request, $inputName, $pageKey, $sectionKey);
                    if (! $ok) {
                        return back()->with('error', $result);
                    }
                    PageContent::put($pageKey, $sectionKey, 'image', $result, $userId);
                } elseif ($request->boolean($inputName . '_clear')) {
                    $this->deleteStoredImage($pageKey, $sectionKey);
                    PageContent::put($pageKey, $sectionKey, 'image', null, $userId);
                }
                // No new file and no "clear" checked → leave the existing image untouched.
                continue;
            }

            // 'text' and 'video_link' both just take the submitted string as-is.
            $value = $request->input($inputName);
            PageContent::put($pageKey, $sectionKey, $meta['type'], $value !== '' ? $value : null, $userId);
        }

        return back()->with('success', 'Page content saved.');
    }

    /**
     * @return array{0: bool, 1: string} [success, publicUrlOrErrorMessage]
     */
    private function storeImage(Request $request, string $inputName, string $pageKey, string $sectionKey): array
    {
        $validator = Validator::make($request->all(), [
            $inputName => 'required|file|mimes:jpg,jpeg,png,webp,gif',
        ]);

        if ($validator->fails()) {
            return [false, $validator->errors()->first($inputName)];
        }

        $file = $request->file($inputName);
        $ext  = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, $this->allowedImageTypes, true)) {
            return [false, 'Only JPG, PNG, WEBP, and GIF images are allowed.'];
        }

        if ($file->getSize() > $this->maxImageSize) {
            return [false, 'Image must be smaller than 5 MB.'];
        }

        // Old file (if any) is removed after the new one is stored — see
        // deleteStoredImage() below, called with the *previous* content_value
        // (fetched before PageContent::put() overwrites it).
        $old = PageContent::query()->where('page_key', $pageKey)->where('section_key', $sectionKey)->value('content_value');

        $filename = $pageKey . '_' . $sectionKey . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $file->storeAs('page-content', $filename, 'public');

        if ($old) {
            $this->deleteStoredImageUrl($old);
        }

        // asset() (request-aware: uses the current request's actual scheme+host)
        // rather than Storage::disk('public')->url(), which bakes in .env's
        // static APP_URL — wrong the moment APP_URL doesn't match how the app
        // is actually being accessed, silently producing a broken <img src>.
        return [true, asset('storage/page-content/' . $filename)];
    }

    private function deleteStoredImage(string $pageKey, string $sectionKey): void
    {
        $old = PageContent::query()->where('page_key', $pageKey)->where('section_key', $sectionKey)->value('content_value');
        if ($old) {
            $this->deleteStoredImageUrl($old);
        }
    }

    /**
     * content_value for images is a full URL built by asset() above —
     * translate back to a disk-relative path to delete it. Matches on the
     * '/storage/page-content/' marker rather than reconstructing and
     * comparing a host prefix, since the host asset() used depends on
     * whatever request generated it and isn't reliably reproducible here.
     */
    private function deleteStoredImageUrl(string $url): void
    {
        $marker = '/storage/page-content/';
        $pos    = strpos($url, $marker);
        if ($pos === false) {
            return; // not one of our stored files (e.g. still pointing at a static /imgs/... fallback) — nothing to delete
        }

        $path = 'page-content/' . substr($url, $pos + strlen($marker));
        Storage::disk('public')->delete($path);
    }
}
