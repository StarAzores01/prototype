<?php

use App\Http\Controllers\BeneficiaryDashboardController;
use App\Http\Controllers\EvaluatorDashboardController;
use App\Http\Controllers\ExtensionCoordinatorDashboardController;
use App\Http\Controllers\ExtensionCoordinatorTrainingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectLeaderDashboardController;
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
});

Route::middleware(['auth', 'role:project_leader'])->group(function () {
    Route::get('/project-leader/dashboard', [ProjectLeaderDashboardController::class, 'index'])
        ->name('project-leader.dashboard');
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
