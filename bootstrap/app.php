<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        // see BUG 3 fix notes in routes/web.php.
        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            if ($request->user('beneficiary')) {
                return route('beneficiary.home');
            }

            if ($user = $request->user('web')) {
                return match ($user->role) {
                    'extension_coordinator' => route('ec.dashboard'),
                    'trainer' => route('trainer.dashboard'),
                    'evaluator' => route('evaluator.dashboard'),
                    default => route('home'),
                };
            }

            return route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
