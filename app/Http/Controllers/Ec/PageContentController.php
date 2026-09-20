<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\HomepageVideo;
use App\Models\PageContent;
use App\Models\Post;
use App\Models\Program;
use App\Models\Training;
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
 * Everything lives on one page (ec/page-content/index.blade.php): a Pages
 * tab whose cards each open an "Edit Content" modal (some with sub-tabs)
 * instead of navigating to a separate edit screen, and a Site Settings tab
 * covering the 'global' page. A single modal can touch more than one
 * page_key at once — the Contact page's modal edits its own 'contact'
 * fields alongside the shared 'global' contact_address/email/phone shown
 * on the same cards — so form inputs are namespaced
 * sections[{page_key}][{section_key}] and update() loops every page_key
 * actually present in the submission, not just one passed in the URL.
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

    /**
     * Every page's current values, keyed [page_key][section_key] => value,
     * so every modal on the Pages/Site Settings tabs can be pre-filled
     * without a per-page round trip.
     */
    public function index(Request $request)
    {
        $pagesConfig = config('page_content_sections');

        $allRows = PageContent::query()
            ->whereIn('page_key', array_keys($pagesConfig))
            ->get();

        $existing = $allRows->groupBy('page_key')->map(fn ($rows) => $rows->pluck('content_value', 'section_key'));

        $values = collect($pagesConfig)->mapWithKeys(function (array $page, string $pageKey) use ($existing) {
            $rowsForPage = $existing->get($pageKey, collect());

            return [$pageKey => collect($page['sections'])->mapWithKeys(fn ($meta, $sectionKey) => [
                $sectionKey => $rowsForPage->get($sectionKey),
            ])];
        });

        // Whether page $pageKey (or the 'global' pseudo-page used by the
        // Site Settings tab) has any draft value that hasn't been
        // published yet — drives the "Unpublished changes" indicators.
        $dirtyPages = $allRows->groupBy('page_key')->map(fn ($rows) => $rows->contains(fn ($row) => $row->hasUnpublishedChanges()));

        // Featured Activities — real Training rows the EC has chosen to
        // feature on the public site (Manage Public Site Content →
        // Trainings). Includes anything currently featured as a draft
        // (is_featured) AND anything still live but pending an unfeature
        // (published_is_featured), so a pending "unfeature" still shows in
        // this admin list with its "Unpublished changes" dot until
        // published. See App\Models\Training::hasUnpublishedFeatureChanges().
        $featuredTrainings = Training::where('is_featured', true)
            ->orWhere('published_is_featured', true)
            ->orderBy('title')
            ->get();

        // Activities not currently featured — offered in the "Existing
        // Activity" picker so EC doesn't feature the same one twice.
        $availableTrainings = Training::where('is_featured', false)
            ->orderBy('title')
            ->get(['id', 'title', 'area']);

        // Programs offered in the "New Activity" quick-add form's
        // required program_id field — same requirement as
        // Ec\TrainingController::create().
        $programsForFeature = Program::orderBy('title')->get(['id', 'title']);

        return view('ec.page-content.index', [
            'activePage' => 'page-content',
            'pagesConfig' => $pagesConfig,
            'values'      => $values,
            'dirtyPages'  => $dirtyPages,
            'featuredTrainings'  => $featuredTrainings,
            'availableTrainings' => $availableTrainings,
            'programsForFeature' => $programsForFeature,
            'hasPendingPublish' => $dirtyPages->contains(true) || $featuredTrainings->contains(fn ($t) => $t->hasUnpublishedFeatureChanges()),
            'homepageVideo' => HomepageVideo::current(),
            'posts'       => Post::with('author')->latest()->get(),
            'activeTab'   => $request->input('tab', 'pages'),
        ]);
    }

    public function update(Request $request)
    {
        $userId = Auth::guard('web')->id();
        $submitted = $request->input('sections', []);
        $tab = $request->input('tab', 'pages');

        foreach (array_keys($submitted) as $pageKey) {
            $page = config("page_content_sections.$pageKey");
            if (! $page) {
                continue; // ignore anything not in the registry rather than 404ing a whole multi-page submit
            }

            foreach ($page['sections'] as $sectionKey => $meta) {
                $inputPath = "sections.$pageKey.$sectionKey";

                if ($meta['type'] === 'image') {
                    if ($request->hasFile($inputPath)) {
                        [$ok, $result] = $this->storeImage($request, $inputPath, $pageKey, $sectionKey);
                        if (! $ok) {
                            return $this->toTab($tab)->with('error', $result);
                        }
                        PageContent::put($pageKey, $sectionKey, 'image', $result, $userId);
                    } elseif ($request->boolean("sections.$pageKey.{$sectionKey}_clear")) {
                        $this->deleteStoredImage($pageKey, $sectionKey);
                        PageContent::put($pageKey, $sectionKey, 'image', null, $userId);
                    }
                    // No new file and no "clear" checked → leave the existing image untouched.
                    continue;
                }

                // 'text' and 'video_link' both just take the submitted string as-is.
                if (! $request->has($inputPath)) {
                    continue; // section wasn't part of this particular modal's fields
                }
                $value = $request->input($inputPath);
                PageContent::put($pageKey, $sectionKey, $meta['type'], $value !== '' ? $value : null, $userId);
            }
        }

        return $this->toTab($tab)->with('success', 'Page content saved as draft. Click "Publish All Changes" to make it live.');
    }

    /**
     * Redirects back to the Manage Public Site Content page with the
     * given tab re-selected (see resources/views/ec/page-content/index.blade.php's
     * openTabFromQueryString()/switchMainTab()), instead of a bare back()/
     * route() that always lands the EC on the default Pages tab.
     */
    private function toTab(string $tab)
    {
        return redirect()->route('ec.page-content', ['tab' => $tab]);
    }

    /**
     * @return array{0: bool, 1: string} [success, publicUrlOrErrorMessage]
     */
    private function storeImage(Request $request, string $inputPath, string $pageKey, string $sectionKey): array
    {
        $validator = Validator::make($request->all(), [
            $inputPath => 'required|file|mimes:jpg,jpeg,png,webp,gif',
        ]);

        if ($validator->fails()) {
            return [false, $validator->errors()->first($inputPath)];
        }

        $file = data_get($request->allFiles(), $inputPath);
        $ext  = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, $this->allowedImageTypes, true)) {
            return [false, 'Only JPG, PNG, WEBP, and GIF images are allowed.'];
        }

        if ($file->getSize() > $this->maxImageSize) {
            return [false, 'Image must be smaller than 5 MB.'];
        }

        // Old file (if any) is removed after the new one is stored — but
        // only if it isn't the file still live on the public site
        // (published_value). Draft edits must never break what's
        // currently published; an orphaned old draft file is cleaned up
        // once publishAll() supersedes published_value instead.
        $row = PageContent::query()->where('page_key', $pageKey)->where('section_key', $sectionKey)->first();
        $old = $row?->content_value;

        $filename = $pageKey . '_' . $sectionKey . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $file->storeAs('page-content', $filename, 'public');

        if ($old && $old !== $row?->published_value) {
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
        $row = PageContent::query()->where('page_key', $pageKey)->where('section_key', $sectionKey)->first();
        $old = $row?->content_value;
        if ($old && $old !== $row?->published_value) {
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
