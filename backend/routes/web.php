<?php

use App\Http\Controllers\BeneficiaryDashboardController;
use App\Http\Controllers\EvaluatorDashboardController;
use App\Http\Controllers\ExtensionCoordinatorDashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BeneficiaryImpactAssessmentController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EvaluationFormController;
use App\Http\Controllers\EvaluationQuestionController;
use App\Http\Controllers\EvaluationResponseController;
use App\Http\Controllers\EvaluatorImpactAssessmentController;
use App\Http\Controllers\ExtensionCoordinatorParticipantController;
use App\Http\Controllers\ExtensionCoordinatorTrainingController;
use App\Http\Controllers\ImpactAssessmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectLeaderDashboardController;
use App\Http\Controllers\ProjectLeaderTrainingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->dashboardRouteName());
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'role:extension_coordinator'])->group(function () {
    Route::get('/extension-coordinator/dashboard', [ExtensionCoordinatorDashboardController::class, 'index'])
        ->name('extension-coordinator.dashboard');

    Route::resource('extension-coordinator/trainings', ExtensionCoordinatorTrainingController::class)
        ->names('extension-coordinator.trainings');

    Route::prefix('extension-coordinator/trainings/{training}/participants')
        ->name('extension-coordinator.trainings.participants.')
        ->group(function () {
            Route::get('/', [ExtensionCoordinatorParticipantController::class, 'index'])->name('index');
            Route::get('/create', [ExtensionCoordinatorParticipantController::class, 'create'])->name('create');
            Route::post('/', [ExtensionCoordinatorParticipantController::class, 'store'])->name('store');
            Route::get('/{participant}/edit', [ExtensionCoordinatorParticipantController::class, 'edit'])->name('edit');
            Route::put('/{participant}', [ExtensionCoordinatorParticipantController::class, 'update'])->name('update');
            Route::delete('/{participant}', [ExtensionCoordinatorParticipantController::class, 'destroy'])->name('destroy');
        });

    Route::prefix('extension-coordinator/trainings/{training}/attendance')
        ->name('extension-coordinator.trainings.attendance.')
        ->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('index');
            Route::post('/', [AttendanceController::class, 'store'])->name('store');
        });

    Route::prefix('extension-coordinator/trainings/{training}/documents')
        ->name('extension-coordinator.trainings.documents.')
        ->group(function () {
            Route::get('/', [DocumentController::class, 'index'])->name('index');
            Route::post('/', [DocumentController::class, 'store'])->name('store');
            Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
            Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
        });

    Route::prefix('extension-coordinator/trainings/{training}/evaluation-forms')
        ->name('extension-coordinator.trainings.evaluation-forms.')
        ->group(function () {
            Route::get('/', [EvaluationFormController::class, 'index'])->name('index');
            Route::get('/create', [EvaluationFormController::class, 'create'])->name('create');
            Route::post('/', [EvaluationFormController::class, 'store'])->name('store');
            Route::get('/{evaluationForm}', [EvaluationFormController::class, 'show'])->name('show');
            Route::post('/{evaluationForm}/publish', [EvaluationFormController::class, 'publish'])->name('publish');
            Route::post('/{evaluationForm}/questions', [EvaluationQuestionController::class, 'store'])->name('questions.store');
        });

    Route::prefix('extension-coordinator/trainings/{training}/impact-assessments')
        ->name('extension-coordinator.trainings.impact-assessments.')
        ->group(function () {
            Route::get('/', [ImpactAssessmentController::class, 'index'])->name('index');
            Route::get('/create', [ImpactAssessmentController::class, 'create'])->name('create');
            Route::post('/', [ImpactAssessmentController::class, 'store'])->name('store');
        });
});

Route::middleware(['auth', 'role:project_leader'])->group(function () {
    Route::get('/project-leader/dashboard', [ProjectLeaderDashboardController::class, 'index'])
        ->name('project-leader.dashboard');

    Route::get('/project-leader/trainings', [ProjectLeaderTrainingController::class, 'index'])
        ->name('project-leader.trainings.index');

    Route::prefix('project-leader/trainings/{training}/attendance')
        ->name('project-leader.trainings.attendance.')
        ->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('index');
            Route::post('/', [AttendanceController::class, 'store'])->name('store');
        });

    Route::prefix('project-leader/trainings/{training}/documents')
        ->name('project-leader.trainings.documents.')
        ->group(function () {
            Route::get('/', [DocumentController::class, 'index'])->name('index');
            Route::post('/', [DocumentController::class, 'store'])->name('store');
            Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
            Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
        });

    Route::prefix('project-leader/trainings/{training}/evaluation-forms')
        ->name('project-leader.trainings.evaluation-forms.')
        ->group(function () {
            Route::get('/', [EvaluationFormController::class, 'index'])->name('index');
            Route::get('/create', [EvaluationFormController::class, 'create'])->name('create');
            Route::post('/', [EvaluationFormController::class, 'store'])->name('store');
            Route::get('/{evaluationForm}', [EvaluationFormController::class, 'show'])->name('show');
            Route::post('/{evaluationForm}/publish', [EvaluationFormController::class, 'publish'])->name('publish');
            Route::post('/{evaluationForm}/questions', [EvaluationQuestionController::class, 'store'])->name('questions.store');
        });

    Route::prefix('project-leader/trainings/{training}/impact-assessments')
        ->name('project-leader.trainings.impact-assessments.')
        ->group(function () {
            Route::get('/', [ImpactAssessmentController::class, 'index'])->name('index');
            Route::get('/create', [ImpactAssessmentController::class, 'create'])->name('create');
            Route::post('/', [ImpactAssessmentController::class, 'store'])->name('store');
        });
});

Route::middleware(['auth', 'role:beneficiary'])->group(function () {
    Route::get('/beneficiary/dashboard', [BeneficiaryDashboardController::class, 'index'])
        ->name('beneficiary.dashboard');

    Route::prefix('beneficiary/evaluation-forms')
        ->name('beneficiary.evaluation-forms.')
        ->group(function () {
            Route::get('/', [EvaluationResponseController::class, 'index'])->name('index');
            Route::get('/{evaluationForm}', [EvaluationResponseController::class, 'show'])->name('show');
            Route::post('/{evaluationForm}', [EvaluationResponseController::class, 'store'])->name('store');
        });

    Route::prefix('beneficiary/impact-assessments')
        ->name('beneficiary.impact-assessments.')
        ->group(function () {
            Route::get('/', [BeneficiaryImpactAssessmentController::class, 'index'])->name('index');
            Route::get('/{impactAssessment}/edit', [BeneficiaryImpactAssessmentController::class, 'edit'])->name('edit');
            Route::put('/{impactAssessment}', [BeneficiaryImpactAssessmentController::class, 'update'])->name('update');
        });
});

Route::middleware(['auth', 'role:evaluator'])->group(function () {
    Route::get('/evaluator/dashboard', [EvaluatorDashboardController::class, 'index'])
        ->name('evaluator.dashboard');

    Route::prefix('evaluator/impact-assessments')
        ->name('evaluator.impact-assessments.')
        ->group(function () {
            Route::get('/', [EvaluatorImpactAssessmentController::class, 'index'])->name('index');
            Route::get('/{impactAssessment}', [EvaluatorImpactAssessmentController::class, 'show'])->name('show');
            Route::post('/{impactAssessment}/review', [EvaluatorImpactAssessmentController::class, 'markReviewed'])->name('review');
        });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
});

require __DIR__.'/auth.php';
