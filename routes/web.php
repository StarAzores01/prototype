<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredBeneficiaryController;
use App\Http\Controllers\Auth\RegisteredEvaluatorController;
use App\Http\Controllers\Auth\RegisteredTrainerController;
use App\Http\Controllers\Beneficiary\HomeController as BeneficiaryHomeController;
use App\Http\Controllers\Ec\DashboardController as EcDashboardController;
use App\Http\Controllers\Ec\DocumentController as EcDocumentController;
use App\Http\Controllers\Ec\EvaluationController as EcEvaluationController;
use App\Http\Controllers\Ec\EvaluatorController as EcEvaluatorController;
use App\Http\Controllers\Ec\ImpactAssessmentController as EcImpactAssessmentController;
use App\Http\Controllers\Ec\MessageController as EcMessageController;
use App\Http\Controllers\Ec\NotificationController as EcNotificationController;
use App\Http\Controllers\Ec\ParticipantController as EcParticipantController;
use App\Http\Controllers\Ec\ProfileController as EcProfileController;
use App\Http\Controllers\Ec\SkillsController as EcSkillsController;
use App\Http\Controllers\Ec\TrainerController as EcTrainerController;
use App\Http\Controllers\Ec\TrainingController as EcTrainingController;
use App\Http\Controllers\Evaluator\DashboardController as EvaluatorDashboardController;
use App\Http\Controllers\Evaluator\ImpactAssessmentController as EvaluatorImpactAssessmentController;
use App\Http\Controllers\Evaluator\ProfileController as EvaluatorProfileController;
use App\Http\Controllers\Trainer\DashboardController as TrainerDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
| Add controllers for about/contact/trainings-public/etc. here as they're
| converted from the original public/*.php pages.
*/
Route::view('/', 'public.landing')->name('home');

/*
|--------------------------------------------------------------------------
| Auth (shared login across all 4 roles, matching the original login.php)
|--------------------------------------------------------------------------
*/
Route::middleware('guest:web,beneficiary')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/trainer-signup', [RegisteredTrainerController::class, 'create'])->name('trainer.signup');
    Route::post('/trainer-signup', [RegisteredTrainerController::class, 'store']);

    Route::get('/evaluator-signup', [RegisteredEvaluatorController::class, 'create'])->name('evaluator.signup');
    Route::post('/evaluator-signup', [RegisteredEvaluatorController::class, 'store']);

    Route::get('/beneficiary-signup', [RegisteredBeneficiaryController::class, 'create'])->name('beneficiary.signup');
    Route::post('/beneficiary-signup', [RegisteredBeneficiaryController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Extension Coordinator (role: extension_coordinator)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:extension_coordinator'])
    ->prefix('ec')
    ->name('ec.')
    ->group(function () {
        // Built:
        Route::get('/dashboard.php', [EcDashboardController::class, 'index'])->name('dashboard');

        Route::get('/trainings.php', [EcTrainingController::class, 'index'])->name('trainings');
        Route::post('/trainings.php', [EcTrainingController::class, 'store'])->name('trainings.store');

        Route::get('/messages.php', [EcMessageController::class, 'index'])->name('messages');
        Route::get('/notifications.php', [EcNotificationController::class, 'index'])->name('notifications');

        Route::get('/profile.php', [EcProfileController::class, 'show'])->name('profile');
        Route::post('/profile.php', [EcProfileController::class, 'update'])->name('profile.update');

        Route::view('/privacy.php', 'ec.privacy', ['activePage' => 'privacy'])->name('privacy');
        Route::view('/terms.php', 'ec.terms', ['activePage' => 'terms'])->name('terms');

        // Scaffolded (route name exists so layout links resolve; each renders
        // a "coming soon" placeholder until its module is converted):
        Route::get('/participants.php', [EcParticipantController::class, 'index'])->name('participants');
        Route::post('/participants.php', [EcParticipantController::class, 'store'])->name('participants.store');
        Route::get('/documents.php', [EcDocumentController::class, 'index'])->name('documents');
        Route::post('/documents.php', [EcDocumentController::class, 'store'])->name('documents.store');
        Route::get('/evaluations.php', [EcEvaluationController::class, 'index'])->name('evaluations');
        Route::post('/evaluations.php', [EcEvaluationController::class, 'store'])->name('evaluations.store');
        Route::get('/impact_assessment.php', [EcImpactAssessmentController::class, 'index'])->name('impact_assessment');
        Route::post('/impact_assessment.php', [EcImpactAssessmentController::class, 'store'])->name('impact_assessment.store');
        Route::get('/skills.php', [EcSkillsController::class, 'index'])->name('skills');
        Route::view('/reports.php', 'ec.coming-soon', ['activePage' => 'reports'])->name('reports');
        Route::get('/trainers.php', [EcTrainerController::class, 'index'])->name('trainers');
        Route::post('/trainers.php', [EcTrainerController::class, 'store'])->name('trainers.store');
        Route::get('/evaluators.php', [EcEvaluatorController::class, 'index'])->name('evaluators');
        Route::post('/evaluators.php', [EcEvaluatorController::class, 'store'])->name('evaluators.store');
    });

/*
|--------------------------------------------------------------------------
| Trainer (role: trainer)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:trainer'])
    ->prefix('trainer')
    ->name('trainer.')
    ->group(function () {
        // Built:
        Route::get('/dashboard.php', [TrainerDashboardController::class, 'index'])->name('dashboard');

        // Scaffolded (route name exists so layout links resolve; each renders
        // a "coming soon" placeholder until its module is converted):
        Route::view('/trainings.php', 'trainer.coming-soon', ['activePage' => 'trainings'])->name('trainings');
        Route::view('/participants.php', 'trainer.coming-soon', ['activePage' => 'participants'])->name('participants');
        Route::view('/attendance.php', 'trainer.coming-soon', ['activePage' => 'attendance'])->name('attendance');
        Route::view('/activity.php', 'trainer.coming-soon', ['activePage' => 'activity'])->name('activity');
        Route::view('/modules.php', 'trainer.coming-soon', ['activePage' => 'modules'])->name('modules');
        Route::view('/skills.php', 'trainer.coming-soon', ['activePage' => 'skills'])->name('skills');
        Route::view('/evaluations.php', 'trainer.coming-soon', ['activePage' => 'evaluations'])->name('evaluations');
        Route::view('/documents.php', 'trainer.coming-soon', ['activePage' => 'documents'])->name('documents');
        Route::view('/notifications.php', 'trainer.coming-soon', ['activePage' => 'notifications'])->name('notifications');
        Route::view('/profile.php', 'trainer.coming-soon', ['activePage' => 'profile'])->name('profile');
    });

/*
|--------------------------------------------------------------------------
| Evaluator (role: evaluator)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:evaluator'])
    ->prefix('evaluator')
    ->name('evaluator.')
    ->group(function () {
        Route::get('/dashboard.php', [EvaluatorDashboardController::class, 'index'])->name('dashboard');

        Route::get('/impact_assessment.php', [EvaluatorImpactAssessmentController::class, 'index'])->name('impact_assessment');
        Route::post('/impact_assessment.php', [EvaluatorImpactAssessmentController::class, 'store'])->name('impact_assessment.store');

        Route::get('/profile.php', [EvaluatorProfileController::class, 'show'])->name('profile');
        Route::post('/profile.php', [EvaluatorProfileController::class, 'update'])->name('profile.update');
    });

/*
|--------------------------------------------------------------------------
| Beneficiary (separate guard/table, not the "role" middleware)
|--------------------------------------------------------------------------
*/
Route::middleware('beneficiary')
    ->prefix('beneficiary')
    ->name('beneficiary.')
    ->group(function () {
        // Built:
        Route::get('/home.php', [BeneficiaryHomeController::class, 'index'])->name('home');

        // Scaffolded (route name exists so layout links resolve; each renders
        // a "coming soon" placeholder until its module is converted):
        Route::view('/trainings.php', 'beneficiary.coming-soon', ['activePage' => 'trainings'])->name('trainings');
        Route::view('/evaluations.php', 'beneficiary.coming-soon', ['activePage' => 'evaluations'])->name('evaluations');
        Route::view('/impact_assessment.php', 'beneficiary.coming-soon', ['activePage' => 'impact_assessment'])->name('impact_assessment');
        Route::view('/skills.php', 'beneficiary.coming-soon', ['activePage' => 'skills'])->name('skills');
        Route::view('/notifications.php', 'beneficiary.coming-soon', ['activePage' => 'notifications'])->name('notifications');
        Route::view('/profile.php', 'beneficiary.coming-soon', ['activePage' => 'profile'])->name('profile');
    });
