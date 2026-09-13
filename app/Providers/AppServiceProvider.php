<?php

namespace App\Providers;

use App\View\Composers\BeneficiaryLayoutComposer;
use App\View\Composers\EcLayoutComposer;
use App\View\Composers\EvaluatorLayoutComposer;
use App\View\Composers\TrainerLayoutComposer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.ec', EcLayoutComposer::class);
        View::composer('layouts.trainer', TrainerLayoutComposer::class);
        View::composer('layouts.evaluator', EvaluatorLayoutComposer::class);
        View::composer('layouts.beneficiary', BeneficiaryLayoutComposer::class);

        // Password-reset links (and any other absolute URL Laravel
        // generates) must come out as https:// in production, even when
        // the app itself sees plain http:// because a reverse proxy in
        // front of it terminates TLS. Scoped to the production environment
        // only, so local http://127.0.0.1/http://localhost development is
        // never affected. This only affects how URLs are generated — it
        // does not redirect or reject any request, so it carries no risk
        // of a redirect loop behind a proxy.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
