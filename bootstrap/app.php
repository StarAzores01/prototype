<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\PostTooLargeException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'beneficiary' => \App\Http\Middleware\EnsureBeneficiary::class,
            'no-back-cache' => \App\Http\Middleware\PreventBackHistoryCache::class,
        ]);

        // Any authenticated user of any role hitting a guest-only page
        // (login/signup/recovery) should land on THEIR OWN dashboard, not
        // Laravel's generic default (the 'home'/'dashboard' named route) —
        // see BUG 3 fix notes in routes/web.php. Reuses the same mapping the
        // post-login redirect and the public nav's "Go to Dashboard" button
        // use, via AuthenticatedSessionController::redirectPathFor().
        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            if ($request->user('beneficiary')) {
                return AuthenticatedSessionController::redirectPathFor('beneficiary');
            }

            if ($user = $request->user('web')) {
                return AuthenticatedSessionController::redirectPathFor($user->role);
            }

            return route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Thrown when an upload exceeds PHP's own post_max_size (a request
        // that gets this far already cleared nginx's client_max_body_size —
        // see public/.user.ini). Without this, the user sees Laravel's bare
        // 413 error page instead of landing back on the form with a
        // message they can act on.
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            return back()->with('error', 'That upload is too large. Please choose a smaller file and try again.');
        });
    })->create();
