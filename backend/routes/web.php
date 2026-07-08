<?php

use App\Http\Controllers\BeneficiaryDashboardController;
use App\Http\Controllers\EvaluatorDashboardController;
use App\Http\Controllers\ExtensionCoordinatorDashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExtensionCoordinatorParticipantController;
use App\Http\Controllers\ExtensionCoordinatorTrainingController;
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
});

Route::middleware(['auth', 'role:beneficiary'])->group(function () {
    Route::get('/beneficiary/dashboard', [BeneficiaryDashboardController::class, 'index'])
        ->name('beneficiary.dashboard');
});

Route::middleware(['auth', 'role:evaluator'])->group(function () {
    Route::get('/evaluator/dashboard', [EvaluatorDashboardController::class, 'index'])
        ->name('evaluator.dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
