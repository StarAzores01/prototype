<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Redirects an old, no-longer-served .php-style URL to its clean
 * replacement, forwarding the original query string.
 *
 * Laravel's built-in Route::redirect() (used for the bulk of the legacy
 * URL renames in routes/web.php) substitutes path parameters correctly but
 * silently drops the query string — fine for routes where none of the old
 * paths ever carried one, but wrong for the handful that do: an
 * already-emailed password-reset link (?token=...) and notification rows
 * already persisted in the database with a stored ?training=... link. This
 * controller (a real class+method, not a route closure) exists so those
 * specific redirects stay `php artisan route:cache`-safe while still
 * preserving the query string.
 */
class LegacyRedirectController extends Controller
{
    public function to(Request $request, string $destination)
    {
        $queryString = $request->getQueryString();

        return redirect($destination . ($queryString ? '?' . $queryString : ''), 301);
    }
}
