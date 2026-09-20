<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicSite\Concerns\ResolvesPublicNavData;
use App\Models\HomepageVideo;
use App\Models\Post;
use App\Models\Training;

class LandingController extends Controller
{
    use ResolvesPublicNavData;

    /**
     * The shared feed/marketing page. All 4 roles land here after login
     * (see AuthenticatedSessionController::store()) instead of being bounced
     * straight to their dashboard, so the nav here always reflects whichever
     * guard (web or beneficiary) is currently authenticated and offers a
     * "Go to Dashboard" link — identically for all 4 roles.
     */
    public function index()
    {
        return view('public.landing', $this->publicNavData() + [
            'homepageVideo' => HomepageVideo::current(),
            // "Courses Offered" is sourced straight from the real `trainings`
            // table — only Activities the EC has explicitly featured under
            // Manage Public Site Content → Trainings and published (see
            // Training::scopeVisibleOnPublicSite / Ec\FeaturedActivityController).
            // scopeVisibleOnPublicSite() switches to EC's own unpublished
            // draft selection while her "View Live" preview is active (see
            // App\Support\PagePreview) so she can see what she's about to
            // publish; every other visitor still only ever sees published
            // rows. Each Training now carries its own display_featured_image
            // accessor, so there's no separate category-photo lookup needed.
            'coursesOffered' => Training::visibleOnPublicSite()
                ->orderBy('date_start')
                ->get(),
            // Posts & Announcements — authored by the Extension Coordinator
            // under Manage Public Site Content → Posts (see App\Models\Post
            // and Ec\PostController). Only published posts, newest first.
            'posts' => Post::published()->with('author')->latest('published_at')->take(6)->get(),
        ]);
    }
}
