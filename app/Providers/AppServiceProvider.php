<?php

namespace App\Providers;

use App\View\Composers\BeneficiaryLayoutComposer;
use App\View\Composers\EcLayoutComposer;
use App\View\Composers\EvaluatorLayoutComposer;
use App\View\Composers\TrainerLayoutComposer;
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
    }
}
