<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredBeneficiaryController;
use App\Http\Controllers\Auth\RegisteredEcController;
use App\Http\Controllers\Auth\RegisteredEvaluatorController;
use App\Http\Controllers\Auth\RegisteredTrainerController;
use App\Http\Controllers\Beneficiary\EvaluationController as BeneficiaryEvaluationController;
use App\Http\Controllers\Beneficiary\EvaluationHubController as BeneficiaryEvaluationHubController;
use App\Http\Controllers\Beneficiary\HomeController as BeneficiaryHomeController;
use App\Http\Controllers\Beneficiary\ImpactAssessmentController as BeneficiaryImpactAssessmentController;
use App\Http\Controllers\Beneficiary\ProfileController as BeneficiaryProfileController;
use App\Http\Controllers\Beneficiary\NotificationController as BeneficiaryNotificationController;
use App\Http\Controllers\Beneficiary\SkillsController as BeneficiarySkillsController;
use App\Http\Controllers\Beneficiary\TrainingController as BeneficiaryTrainingController;
use App\Http\Controllers\Ec\AnalyticsController as EcAnalyticsController;
use App\Http\Controllers\Ec\DashboardController as EcDashboardController;
use App\Http\Controllers\Ec\DocumentController as EcDocumentController;
use App\Http\Controllers\Ec\EvaluationController as EcEvaluationController;
use App\Http\Controllers\Ec\EvaluationHubController as EcEvaluationHubController;
use App\Http\Controllers\Ec\EvaluatorController as EcEvaluatorController;
use App\Http\Controllers\Ec\ImpactAssessmentController as EcImpactAssessmentController;
use App\Http\Controllers\Ec\MessageController as EcMessageController;
use App\Http\Controllers\Ec\NotificationController as EcNotificationController;
use App\Http\Controllers\Ec\PageContentController as EcPageContentController;
use App\Http\Controllers\Ec\ParticipantController as EcParticipantController;
use App\Http\Controllers\Ec\ProfileController as EcProfileController;
use App\Http\Controllers\Ec\ProgramController as EcProgramController;
use App\Http\Controllers\Ec\ReportController as EcReportController;
use App\Http\Controllers\Ec\SkillsController as EcSkillsController;
use App\Http\Controllers\Ec\TrainerController as EcTrainerController;
use App\Http\Controllers\Ec\TrainingController as EcTrainingController;
use App\Http\Controllers\Evaluator\DashboardController as EvaluatorDashboardController;
use App\Http\Controllers\Evaluator\EvaluationHubController as EvaluatorEvaluationHubController;
use App\Http\Controllers\Evaluator\ImpactAssessmentController as EvaluatorImpactAssessmentController;
use App\Http\Controllers\Evaluator\ProfileController as EvaluatorProfileController;
use App\Http\Controllers\FileDownloadController;
use App\Http\Controllers\PublicSite\AboutController;
use App\Http\Controllers\PublicSite\ChooseRoleController;
use App\Http\Controllers\PublicSite\ContactController;
use App\Http\Controllers\PublicSite\LandingController;
use App\Http\Controllers\PublicSite\TrainingsPublicController;
use App\Http\Controllers\Trainer\ActivityController as TrainerActivityController;
use App\Http\Controllers\Trainer\AttendanceController as TrainerAttendanceController;
use App\Http\Controllers\Trainer\DashboardController as TrainerDashboardController;
use App\Http\Controllers\Trainer\DocumentController as TrainerDocumentController;
use App\Http\Controllers\Trainer\EvaluationController as TrainerEvaluationController;
use App\Http\Controllers\Trainer\EvaluationHubController as TrainerEvaluationHubController;
use App\Http\Controllers\Trainer\ModuleController as TrainerModuleController;
use App\Http\Controllers\Trainer\NotificationController as TrainerNotificationController;
use App\Http\Controllers\Trainer\ParticipantController as TrainerParticipantController;
use App\Http\Controllers\Trainer\ProfileController as TrainerProfileController;
use App\Http\Controllers\Trainer\ProgramController as TrainerProgramController;
use App\Http\Controllers\Trainer\SkillsController as TrainerSkillsController;
use App\Http\Controllers\Trainer\TrainingController as TrainerTrainingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
| Converted from the original public/*.php marketing pages.
*/
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/choose-role.php', [ChooseRoleController::class, 'index'])->name('choose-role');
Route::get('/about.php', [AboutController::class, 'index'])->name('about');
Route::get('/trainings-public.php', [TrainingsPublicController::class, 'index'])->name('trainings-public');
Route::view('/privacy.php', 'public.privacy')->name('privacy');
Route::view('/terms.php', 'public.terms')->name('terms');
Route::get('/contact.php', [ContactController::class, 'index'])->name('contact');
Route::post('/contact.php', [ContactController::class, 'store'])->name('contact.store');

/*
|--------------------------------------------------------------------------
| Auth (shared login across all 4 roles, matching the original login.php)
|--------------------------------------------------------------------------
*/
Route::middleware(['guest:web,beneficiary', 'no-back-cache'])->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::get('/trainer-signup', [RegisteredTrainerController::class, 'create'])->name('trainer.signup');
    Route::post('/trainer-signup', [RegisteredTrainerController::class, 'store']);

    Route::get('/evaluator-signup', [RegisteredEvaluatorController::class, 'create'])->name('evaluator.signup');
    Route::post('/evaluator-signup', [RegisteredEvaluatorController::class, 'store']);

    Route::get('/beneficiary-signup', [RegisteredBeneficiaryController::class, 'create'])->name('beneficiary.signup');
    Route::post('/beneficiary-signup', [RegisteredBeneficiaryController::class, 'store']);

    // EC self-registration — no whitelist gate, matches ecsignuppage.php.
    Route::get('/ecsignuppage.php', [RegisteredEcController::class, 'create'])->name('ec.signup');
    Route::post('/ecsignuppage.php', [RegisteredEcController::class, 'store']);

    // EC-only forgot-password flow, matches ecrecovery.php.
    Route::get('/ecrecovery.php', [PasswordResetController::class, 'create'])->name('ec.recovery');
    Route::post('/ecrecovery.php', [PasswordResetController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated file downloads (any of the 4 roles)
|--------------------------------------------------------------------------
| Replaces the old direct asset('storage/uploads/...') links, which served
| every uploaded file as a public, unauthenticated static URL regardless of
| its declared visibility. See FileDownloadController for the per-type
| access rules.
*/
Route::middleware(['auth:web,beneficiary', 'no-back-cache'])->group(function () {
    Route::get('/files/documents/{document}', [FileDownloadController::class, 'document'])->name('files.document');
    Route::get('/files/training-docs/{trainingDoc}', [FileDownloadController::class, 'trainingDoc'])->name('files.training-doc');
    Route::get('/files/impact-assessments/{impactAssessment}', [FileDownloadController::class, 'impactAssessment'])->name('files.impact-assessment');
    Route::get('/files/activity-cover/{training}', [FileDownloadController::class, 'activityCover'])->name('files.activity-cover');
    Route::get('/files/program-cover/{program}', [FileDownloadController::class, 'programCover'])->name('files.program-cover');
    Route::get('/files/avatar', [FileDownloadController::class, 'avatar'])->name('files.avatar');
});

/*
|--------------------------------------------------------------------------
| Extension Coordinator (role: extension_coordinator)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:extension_coordinator', 'no-back-cache'])
    ->prefix('ec')
    ->name('ec.')
    ->group(function () {
        Route::get('/dashboard.php', [EcDashboardController::class, 'index'])->name('dashboard');
        Route::post('/dashboard.php', [EcDashboardController::class, 'store'])->name('dashboard.store');

        Route::get('/programs.php', [EcProgramController::class, 'index'])->name('programs');
        Route::post('/programs.php', [EcProgramController::class, 'store'])->name('programs.store');

        Route::get('/trainings.php', [EcTrainingController::class, 'index'])->name('trainings');
        Route::post('/trainings.php', [EcTrainingController::class, 'store'])->name('trainings.store');

        Route::get('/messages.php', [EcMessageController::class, 'index'])->name('messages');
        Route::get('/notifications.php', [EcNotificationController::class, 'index'])->name('notifications');

        Route::get('/profile.php', [EcProfileController::class, 'show'])->name('profile');
        Route::post('/profile.php', [EcProfileController::class, 'update'])->name('profile.update');

        Route::view('/privacy.php', 'ec.privacy', ['activePage' => 'privacy'])->name('privacy');
        Route::view('/terms.php', 'ec.terms', ['activePage' => 'terms'])->name('terms');

        Route::get('/participants.php', [EcParticipantController::class, 'index'])->name('participants');
        Route::post('/participants.php', [EcParticipantController::class, 'store'])->name('participants.store');
        Route::get('/documents.php', [EcDocumentController::class, 'index'])->name('documents');
        Route::post('/documents.php', [EcDocumentController::class, 'store'])->name('documents.store');
        Route::get('/evaluation.php', [EcEvaluationHubController::class, 'index'])->name('evaluation');

        Route::get('/evaluations.php', [EcEvaluationController::class, 'index'])->name('evaluations');
        Route::post('/evaluations.php', [EcEvaluationController::class, 'store'])->name('evaluations.store');
        Route::get('/impact_assessment.php', [EcImpactAssessmentController::class, 'index'])->name('impact_assessment');
        Route::post('/impact_assessment.php', [EcImpactAssessmentController::class, 'store'])->name('impact_assessment.store');
        Route::get('/skills.php', [EcSkillsController::class, 'index'])->name('skills');
        Route::get('/reports.php', [EcReportController::class, 'index'])->name('reports');
        Route::get('/analytics.php', [EcAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/trainers.php', [EcTrainerController::class, 'index'])->name('trainers');
        Route::post('/trainers.php', [EcTrainerController::class, 'store'])->name('trainers.store');
        Route::get('/evaluators.php', [EcEvaluatorController::class, 'index'])->name('evaluators');
        Route::post('/evaluators.php', [EcEvaluatorController::class, 'store'])->name('evaluators.store');

        Route::get('/page-content.php', [EcPageContentController::class, 'index'])->name('page-content');
        Route::get('/page-content.php/{pageKey}', [EcPageContentController::class, 'edit'])->name('page-content.edit');
        Route::post('/page-content.php/{pageKey}', [EcPageContentController::class, 'update'])->name('page-content.update');
    });

/*
|--------------------------------------------------------------------------
| Trainer (role: trainer)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:trainer', 'no-back-cache'])
    ->prefix('trainer')
    ->name('trainer.')
    ->group(function () {
        Route::get('/dashboard.php', [TrainerDashboardController::class, 'index'])->name('dashboard');

        // Scoped version of Ec's Programs feature — a trainer only sees/acts on
        // programs they belong to (see Trainer\ProgramController and
        // Program::scopeVisibleToTrainer()). Sub-actions (create, request_unlock,
        // update_status, update_team, upload) all dispatch through this single
        // POST route via the "action" field, same convention as every other
        // resource in this app (Ec\ProgramController included) — no separate
        // per-action routes.
        Route::get('/programs.php', [TrainerProgramController::class, 'index'])->name('programs');
        Route::post('/programs.php', [TrainerProgramController::class, 'store'])->name('programs.store');

        Route::get('/trainings.php', [TrainerTrainingController::class, 'index'])->name('trainings');
        Route::post('/trainings.php', [TrainerTrainingController::class, 'store'])->name('trainings.store');
        Route::get('/participants.php', [TrainerParticipantController::class, 'index'])->name('participants');
        Route::get('/attendance.php', [TrainerAttendanceController::class, 'index'])->name('attendance');
        Route::post('/attendance.php', [TrainerAttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/activity.php', [TrainerActivityController::class, 'index'])->name('activity');
        Route::post('/activity.php', [TrainerActivityController::class, 'store'])->name('activity.store');
        Route::get('/modules.php', [TrainerModuleController::class, 'index'])->name('modules');
        Route::post('/modules.php', [TrainerModuleController::class, 'store'])->name('modules.store');
        Route::get('/evaluation.php', [TrainerEvaluationHubController::class, 'index'])->name('evaluation');

        Route::get('/skills.php', [TrainerSkillsController::class, 'index'])->name('skills');
        Route::post('/skills.php', [TrainerSkillsController::class, 'store'])->name('skills.store');
        Route::get('/evaluations.php', [TrainerEvaluationController::class, 'index'])->name('evaluations');
        Route::get('/documents.php', [TrainerDocumentController::class, 'index'])->name('documents');
        Route::post('/documents.php', [TrainerDocumentController::class, 'store'])->name('documents.store');
        Route::get('/notifications.php', [TrainerNotificationController::class, 'index'])->name('notifications');
        Route::get('/profile.php', [TrainerProfileController::class, 'show'])->name('profile');
        Route::post('/profile.php', [TrainerProfileController::class, 'update'])->name('profile.update');
    });

/*
|--------------------------------------------------------------------------
| Evaluator (role: evaluator)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'role:evaluator', 'no-back-cache'])
    ->prefix('evaluator')
    ->name('evaluator.')
    ->group(function () {
        Route::get('/dashboard.php', [EvaluatorDashboardController::class, 'index'])->name('dashboard');

        Route::get('/evaluation.php', [EvaluatorEvaluationHubController::class, 'index'])->name('evaluation');

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
Route::middleware(['beneficiary', 'no-back-cache'])
    ->prefix('beneficiary')
    ->name('beneficiary.')
    ->group(function () {
        Route::get('/home.php', [BeneficiaryHomeController::class, 'index'])->name('home');

        Route::get('/trainings.php', [BeneficiaryTrainingController::class, 'index'])->name('trainings');

        Route::get('/evaluation.php', [BeneficiaryEvaluationHubController::class, 'index'])->name('evaluation');

        Route::get('/evaluations.php', [BeneficiaryEvaluationController::class, 'index'])->name('evaluations');
        Route::post('/evaluations.php', [BeneficiaryEvaluationController::class, 'store'])->name('evaluations.store');
        Route::get('/impact_assessment.php', [BeneficiaryImpactAssessmentController::class, 'index'])->name('impact_assessment');
        Route::post('/impact_assessment.php', [BeneficiaryImpactAssessmentController::class, 'store'])->name('impact_assessment.store');
        Route::get('/skills.php', [BeneficiarySkillsController::class, 'index'])->name('skills');
        Route::post('/skills.php', [BeneficiarySkillsController::class, 'store'])->name('skills.store');
        Route::get('/notifications.php', [BeneficiaryNotificationController::class, 'index'])->name('notifications');
        Route::get('/profile.php', [BeneficiaryProfileController::class, 'show'])->name('profile');
        Route::post('/profile.php', [BeneficiaryProfileController::class, 'update'])->name('profile.update');
    });
