<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to authenticated beneficiaries (separate guard/table
 * from staff users).
 */
class EnsureBeneficiary
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user('beneficiary')) {
            return redirect()->route('login')->with('error', 'Please log in to continue.');
        }

        return $next($request);
    }
}
