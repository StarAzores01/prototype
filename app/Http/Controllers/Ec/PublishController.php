<?php

namespace App\Http\Controllers\Ec;

use App\Http\Controllers\Controller;
use App\Models\PageContent;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * "Publish All Changes" — the single action, bound to the button in the
 * Manage Public Site Content page header, that takes every pending draft
 * edit across the Pages, Site Settings, and Trainings tabs and makes it
 * live on the public site in one transaction.
 *
 * Deliberately does NOT touch Posts: posts already have their own
 * per-post draft/published workflow (App\Models\Post::status, toggled
 * from Ec\PostController's own Publish/Unpublish buttons on each post),
 * so there's nothing left "pending" for this button to push for them.
 */
class PublishController extends Controller
{
    public function publishAll(Request $request)
    {
        $pagesPublished = 0;
        $featuredPublished = 0;

        DB::transaction(function () use (&$pagesPublished, &$featuredPublished) {
            // Pages + Site Settings — both stored as page_contents rows.
            $pagesPublished = PageContent::publishAll();

            // Trainings (featured Activities) — see
            // App\Models\Training::hasUnpublishedFeatureChanges()/publishFeature().
            Training::where('is_featured', true)
                ->orWhere('published_is_featured', true)
                ->get()
                ->each(function (Training $training) use (&$featuredPublished) {
                    if ($training->hasUnpublishedFeatureChanges()) {
                        $training->publishFeature();
                        $featuredPublished++;
                    }
                });
        });

        $total = $pagesPublished + $featuredPublished;

        $message = $total > 0
            ? "Published {$total} change" . ($total === 1 ? '' : 's') . ' to the live site.'
            : 'Nothing to publish — the public site already matches your latest edits.';

        return redirect()
            ->route('ec.page-content', ['tab' => $request->input('tab', 'pages')])
            ->with('success', $message);
    }
}
