<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::guard('web')->user();

        if (
            $user &&
            $user->must_change_password &&
            !$request->routeIs('password.force.*') &&
            !$request->routeIs('logout')
        ) {
            return Redirect::route('password.force.form');
        }

        return $next($request);
    }
}
