<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces the browser to re-request every authenticated page instead of
 * serving it from its disk cache or back-forward cache (bfcache).
 *
 * Without this, PHP's own session.cache_limiter=nocache setting emits a
 * bare "Cache-Control: no-cache, private" on every response — but that
 * directive alone only forces *revalidation*, it doesn't stop bfcache. The
 * browser can still replay the fully-rendered page from memory on a
 * back/forward navigation without contacting the server at all, so the
 * session check never runs and a logged-out user sees the stale
 * authenticated page. `no-store` is the directive that actually disqualifies
 * the page from bfcache.
 *
 * Applied to the auth:web and beneficiary middleware groups (see
 * routes/web.php) so every page gated behind a login requirement is
 * covered, regardless of which guard protects it.
 *
 * Also applied to the guest:web,beneficiary group (login, signup, forgot-
 * password) — NOT because those pages are sensitive themselves, but
 * because /login renders a one-shot session flash message (e.g. "You have
 * been logged out.") straight into the HTML. Without no-store, that
 * specific render of /login is a valid bfcache/disk-cache candidate, and a
 * later Back navigation that lands on that exact history entry can replay
 * the stale flash text without ever contacting the server — indistinguishable
 * to the user from actually being logged out again. See BUG 4 fix notes.
 */
class PreventBackHistoryCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
