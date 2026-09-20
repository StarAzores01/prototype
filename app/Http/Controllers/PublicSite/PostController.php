<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicSite\Concerns\ResolvesPublicNavData;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

/**
 * Public detail page for a single published Post & Announcement, linked
 * from the landing page's Posts & Announcements section. Increments the
 * post's view counter (once per request, via an atomic increment rather
 * than a read-then-save, so concurrent views can't clobber each other).
 */
class PostController extends Controller
{
    use ResolvesPublicNavData;

    /** Full list of published Posts & Announcements — "View all" from the landing page. */
    public function index()
    {
        return view('public.posts-index', $this->publicNavData() + [
            'posts' => Post::published()->with('author')->latest('published_at')->get(),
        ]);
    }

    public function show(Post $post)
    {
        abort_unless($post->isPublished(), 404);

        DB::table('posts')->where('id', $post->id)->increment('views');

        return view('public.posts-show', $this->publicNavData() + [
            'post' => $post,
        ]);
    }
}
