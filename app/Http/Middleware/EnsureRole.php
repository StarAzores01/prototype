<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to one or more roles on the "web" (staff) guard.
 * Usage in routes: ->middleware('role:extension_coordinator')
 *                  ->middleware('role:extension_coordinator,trainer')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user('web');

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'You are not authorized to access this page.');
        }

        return $next($request);
    }
}
