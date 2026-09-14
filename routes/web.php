<?php

use App\Http\Controllers\Auth\ForcePasswordChangeController;
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
use App\Http\Controllers\LegacyRedirectController;
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
| Converted from the original public/*.php marketing pages. URIs are now
| extension-less ("clean URLs") — see the legacy-redirect block below for
| the old .php paths. Route *names* are unchanged throughout this whole
| file, so every route('...') call in the views keeps working untouched;
| only the URI string each name maps to has changed.
*/
Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/choose-role', [ChooseRoleController::class, 'index'])->name('choose-role');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/trainings-public', [TrainingsPublicController::class, 'index'])->name('trainings-public');
Route::view('/privacy', 'public.privacy')->name('privacy');
Route::view('/terms', 'public.terms')->name('terms');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

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

    // EC self-registration — no whitelist gate. Moved under /account/ (was
    // the top-level /ecsignuppage.php).
    Route::get('/account/ec-signup', [RegisteredEcController::class, 'create'])->name('ec.signup');
    Route::post('/account/ec-signup', [RegisteredEcController::class, 'store']);

    // Staff (EC / trainer / evaluator) forgot-password flow — shared by all
    // 3 "web" guard roles (originally EC-only, matching ecrecovery.php).
    // Beneficiaries have their own separate guard/table and are not
    // reachable through this controller at all. Moved under /account/ (was
    // the top-level /ecrecovery.php).
    Route::get('/account/recovery', [PasswordResetController::class, 'create'])->name('ec.recovery');
    Route::post('/account/recovery', [PasswordResetController::class, 'store'])
        ->middleware('throttle:5,1');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

Route::middleware(['auth:web', 'no-back-cache'])->group(function () {
    Route::get(
        '/change-temporary-password',
        [ForcePasswordChangeController::class, 'edit']
    )->name('password.force.form');

    Route::post(
        '/change-temporary-password',
        [ForcePasswordChangeController::class, 'update']
    )->name('password.force.update');
});

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
        Route::get('/dashboard', [EcDashboardController::class, 'index'])->name('dashboard');
        Route::post('/dashboard', [EcDashboardController::class, 'store'])->name('dashboard.store');

        Route::get('/programs', [EcProgramController::class, 'index'])->name('programs');
        Route::post('/programs', [EcProgramController::class, 'store'])->name('programs.store');

        Route::get('/trainings', [EcTrainingController::class, 'index'])->name('trainings');
        Route::post('/trainings', [EcTrainingController::class, 'store'])->name('trainings.store');

        Route::get('/messages', [EcMessageController::class, 'index'])->name('messages');
        Route::get('/notifications', [EcNotificationController::class, 'index'])->name('notifications');

        Route::get('/profile', [EcProfileController::class, 'show'])->name('profile');
        Route::post('/profile', [EcProfileController::class, 'update'])->name('profile.update');

        Route::view('/privacy', 'ec.privacy', ['activePage' => 'privacy'])->name('privacy');
        Route::view('/terms', 'ec.terms', ['activePage' => 'terms'])->name('terms');

        Route::get('/participants', [EcParticipantController::class, 'index'])->name('participants');
        Route::post('/participants', [EcParticipantController::class, 'store'])->name('participants.store');
        Route::get('/documents', [EcDocumentController::class, 'index'])->name('documents');
        Route::post('/documents', [EcDocumentController::class, 'store'])->name('documents.store');
        Route::get('/evaluation', [EcEvaluationHubController::class, 'index'])->name('evaluation');

        Route::get('/evaluations', [EcEvaluationController::class, 'index'])->name('evaluations');
        Route::post('/evaluations', [EcEvaluationController::class, 'store'])->name('evaluations.store');
        Route::get('/impact-assessment', [EcImpactAssessmentController::class, 'index'])->name('impact_assessment');
        Route::post('/impact-assessment', [EcImpactAssessmentController::class, 'store'])->name('impact_assessment.store');
        Route::get('/skills', [EcSkillsController::class, 'index'])->name('skills');
        Route::get('/reports', [EcReportController::class, 'index'])->name('reports');
        Route::get('/analytics', [EcAnalyticsController::class, 'index'])->name('analytics');
        Route::get('/trainers', [EcTrainerController::class, 'index'])->name('trainers');
        Route::post('/trainers', [EcTrainerController::class, 'store'])->name('trainers.store');
        Route::get('/evaluators', [EcEvaluatorController::class, 'index'])->name('evaluators');
        Route::post('/evaluators', [EcEvaluatorController::class, 'store'])->name('evaluators.store');

        Route::get('/page-content', [EcPageContentController::class, 'index'])->name('page-content');
        Route::get('/page-content/{pageKey}', [EcPageContentController::class, 'edit'])->name('page-content.edit');
        Route::post('/page-content/{pageKey}', [EcPageContentController::class, 'update'])->name('page-content.update');
    });

// Legacy .php URL redirects (kept for old bookmarks and any links already
// emailed/stored before this clean-URL migration; nothing in the app links
// to these anymore). GET only — the equivalent POST endpoints above simply
// replace their .php predecessors, no form ever posts to one of these old
// paths since every <form> in the app builds its action from route(), not
// a literal string.
//
// Deliberately its own group, sharing the same middleware+prefix as above
// but WITHOUT ->name('ec.'): a route with no ->name() of its own that sits
// inside a name-prefixed group silently inherits the bare prefix ('ec.')
// as its name — with 20 such routes that means all 20 would collide under
// that one shared, ambiguous name (confirmed: route('ec.') resolves to an
// arbitrary one of them). Nothing in the app calls that name today, but
// keeping these in an unnamed sibling group avoids the landmine entirely.
Route::middleware(['auth:web', 'role:extension_coordinator', 'no-back-cache'])
    ->prefix('ec')
    ->group(function () {
        foreach ([
            '/dashboard.php'            => '/ec/dashboard',
            '/programs.php'             => '/ec/programs',
            '/trainings.php'            => '/ec/trainings',
            '/messages.php'             => '/ec/messages',
            '/notifications.php'        => '/ec/notifications',
            '/profile.php'              => '/ec/profile',
            '/privacy.php'              => '/ec/privacy',
            '/terms.php'                => '/ec/terms',
            '/participants.php'         => '/ec/participants',
            '/documents.php'            => '/ec/documents',
            '/evaluation.php'           => '/ec/evaluation',
            '/evaluations.php'          => '/ec/evaluations',
            '/impact_assessment.php'    => '/ec/impact-assessment',
            '/skills.php'               => '/ec/skills',
            '/reports.php'              => '/ec/reports',
            '/analytics.php'            => '/ec/analytics',
            '/trainers.php'             => '/ec/trainers',
            '/evaluators.php'           => '/ec/evaluators',
            '/page-content.php'         => '/ec/page-content',
            '/page-content.php/{pageKey}' => '/ec/page-content/{pageKey}',
        ] as $old => $new) {
            Route::get($old, \Illuminate\Routing\RedirectController::class)
                ->defaults('destination', $new)
                ->defaults('status', 301); // GET/HEAD only — never swallow a stray POST from a stale pre-deploy page
        }
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
        Route::get('/dashboard', [TrainerDashboardController::class, 'index'])->name('dashboard');

        // Scoped version of Ec's Programs feature — a trainer only sees/acts on
        // programs they belong to (see Trainer\ProgramController and
        // Program::scopeVisibleToTrainer()). Sub-actions (create, update_status,
        // update_team, upload) all dispatch through this single POST route via
        // the "action" field, same convention as every other resource in this
        // app (Ec\ProgramController included) — no separate per-action routes.
        // No timeline/extension action here — only EC can extend a program's
        // effective end date (Ec\ProgramController::extendTimeline()).
        Route::get('/programs', [TrainerProgramController::class, 'index'])->name('programs');
        Route::post('/programs', [TrainerProgramController::class, 'store'])->name('programs.store');

        Route::get('/trainings', [TrainerTrainingController::class, 'index'])->name('trainings');
        Route::post('/trainings', [TrainerTrainingController::class, 'store'])->name('trainings.store');
        Route::get('/participants', [TrainerParticipantController::class, 'index'])->name('participants');
        Route::get('/attendance', [TrainerAttendanceController::class, 'index'])->name('attendance');
        Route::post('/attendance', [TrainerAttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/activity', [TrainerActivityController::class, 'index'])->name('activity');
        Route::post('/activity', [TrainerActivityController::class, 'store'])->name('activity.store');
        Route::get('/modules', [TrainerModuleController::class, 'index'])->name('modules');
        Route::post('/modules', [TrainerModuleController::class, 'store'])->name('modules.store');
        Route::get('/evaluation', [TrainerEvaluationHubController::class, 'index'])->name('evaluation');

        Route::get('/skills', [TrainerSkillsController::class, 'index'])->name('skills');
        Route::post('/skills', [TrainerSkillsController::class, 'store'])->name('skills.store');
        Route::get('/evaluations', [TrainerEvaluationController::class, 'index'])->name('evaluations');
        Route::get('/documents', [TrainerDocumentController::class, 'index'])->name('documents');
        Route::post('/documents', [TrainerDocumentController::class, 'store'])->name('documents.store');
        Route::get('/notifications', [TrainerNotificationController::class, 'index'])->name('notifications');
        Route::get('/profile', [TrainerProfileController::class, 'show'])->name('profile');
        Route::post('/profile', [TrainerProfileController::class, 'update'])->name('profile.update');
    });

// Legacy .php redirects — see the equivalent EC block above for why these
// are deliberately an unnamed sibling group (same middleware+prefix, no
// ->name('trainer.')) rather than living inside the named group.
// /evaluations.php can carry a stored ?training= link from a notification
// row created before this migration, so it goes through
// LegacyRedirectController (forwards the query string) instead of a plain
// Route::redirect().
Route::middleware(['auth:web', 'role:trainer', 'no-back-cache'])
    ->prefix('trainer')
    ->group(function () {
        Route::get('/evaluations.php', [LegacyRedirectController::class, 'to'])
            ->defaults('destination', '/trainer/evaluations');

        foreach ([
            '/dashboard.php'     => '/trainer/dashboard',
            '/programs.php'      => '/trainer/programs',
            '/trainings.php'     => '/trainer/trainings',
            '/participants.php'  => '/trainer/participants',
            '/attendance.php'    => '/trainer/attendance',
            '/activity.php'      => '/trainer/activity',
            '/modules.php'       => '/trainer/modules',
            '/evaluation.php'    => '/trainer/evaluation',
            '/skills.php'        => '/trainer/skills',
            '/documents.php'     => '/trainer/documents',
            '/notifications.php' => '/trainer/notifications',
            '/profile.php'       => '/trainer/profile',
        ] as $old => $new) {
            Route::get($old, \Illuminate\Routing\RedirectController::class)
                ->defaults('destination', $new)
                ->defaults('status', 301); // GET/HEAD only — never swallow a stray POST from a stale pre-deploy page
        }
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
        Route::get('/dashboard', [EvaluatorDashboardController::class, 'index'])->name('dashboard');

        Route::get('/evaluation', [EvaluatorEvaluationHubController::class, 'index'])->name('evaluation');

        Route::get('/impact-assessment', [EvaluatorImpactAssessmentController::class, 'index'])->name('impact_assessment');
        Route::post('/impact-assessment', [EvaluatorImpactAssessmentController::class, 'store'])->name('impact_assessment.store');

        Route::get('/profile', [EvaluatorProfileController::class, 'show'])->name('profile');
        Route::post('/profile', [EvaluatorProfileController::class, 'update'])->name('profile.update');
    });

// Legacy .php redirects — see the equivalent EC block above for why these
// are deliberately an unnamed sibling group (same middleware+prefix, no
// ->name('evaluator.')) rather than living inside the named group.
Route::middleware(['auth:web', 'role:evaluator', 'no-back-cache'])
    ->prefix('evaluator')
    ->group(function () {
        foreach ([
            '/dashboard.php'         => '/evaluator/dashboard',
            '/evaluation.php'        => '/evaluator/evaluation',
            '/impact_assessment.php' => '/evaluator/impact-assessment',
            '/profile.php'           => '/evaluator/profile',
        ] as $old => $new) {
            Route::get($old, \Illuminate\Routing\RedirectController::class)
                ->defaults('destination', $new)
                ->defaults('status', 301); // GET/HEAD only — never swallow a stray POST from a stale pre-deploy page
        }
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
        Route::get('/home', [BeneficiaryHomeController::class, 'index'])->name('home');

        Route::get('/trainings', [BeneficiaryTrainingController::class, 'index'])->name('trainings');

        Route::get('/evaluation', [BeneficiaryEvaluationHubController::class, 'index'])->name('evaluation');

        Route::get('/evaluations', [BeneficiaryEvaluationController::class, 'index'])->name('evaluations');
        Route::post('/evaluations', [BeneficiaryEvaluationController::class, 'store'])->name('evaluations.store');
        Route::get('/impact-assessment', [BeneficiaryImpactAssessmentController::class, 'index'])->name('impact_assessment');
        Route::post('/impact-assessment', [BeneficiaryImpactAssessmentController::class, 'store'])->name('impact_assessment.store');
        Route::get('/skills', [BeneficiarySkillsController::class, 'index'])->name('skills');
        Route::post('/skills', [BeneficiarySkillsController::class, 'store'])->name('skills.store');
        Route::get('/notifications', [BeneficiaryNotificationController::class, 'index'])->name('notifications');
        Route::get('/profile', [BeneficiaryProfileController::class, 'show'])->name('profile');
        Route::post('/profile', [BeneficiaryProfileController::class, 'update'])->name('profile.update');
    });

// Legacy .php redirects — see the equivalent EC block above for why these
// are deliberately an unnamed sibling group (same middleware+prefix, no
// ->name('beneficiary.')) rather than living inside the named group.
// evaluations.php, impact_assessment.php and skills.php can all carry a
// stored ?training= link from a notification row created before this
// migration, so they go through LegacyRedirectController (which forwards
// the query string) instead of a plain Route::redirect().
Route::middleware(['beneficiary', 'no-back-cache'])
    ->prefix('beneficiary')
    ->group(function () {
        foreach ([
            '/evaluations.php'       => '/beneficiary/evaluations',
            '/impact_assessment.php' => '/beneficiary/impact-assessment',
            '/skills.php'            => '/beneficiary/skills',
        ] as $old => $new) {
            Route::get($old, [LegacyRedirectController::class, 'to'])->defaults('destination', $new);
        }

        foreach ([
            '/home.php'          => '/beneficiary/home',
            '/trainings.php'     => '/beneficiary/trainings',
            '/evaluation.php'    => '/beneficiary/evaluation',
            '/notifications.php' => '/beneficiary/notifications',
            '/profile.php'       => '/beneficiary/profile',
        ] as $old => $new) {
            Route::get($old, \Illuminate\Routing\RedirectController::class)
                ->defaults('destination', $new)
                ->defaults('status', 301); // GET/HEAD only — never swallow a stray POST from a stale pre-deploy page
        }
    });

/*
|--------------------------------------------------------------------------
| Legacy top-level .php URL redirects
|--------------------------------------------------------------------------
| Public marketing pages and the two moved /account/* auth pages. GET only
| — no old POST endpoint is redirected anywhere in this file (a redirected
| POST would silently turn into a GET and drop the submitted form data, or
| require the client to natively replay the method+body, which cannot be
| relied on — the safe fix is simply that the new canonical POST endpoint
| is the only one that ever existed at its clean path; nothing legacy to
| preserve since every <form> in the app already posts via route()).
|
| /account/recovery specifically goes through LegacyRedirectController
| (query-string preserving) rather than a plain Route::redirect(), because
| an already-emailed password-reset link is ?token=... — a plain redirect
| would silently drop that token and break any reset link sent before this
| deploy.
*/
Route::get('/ecrecovery.php', [LegacyRedirectController::class, 'to'])
    ->defaults('destination', '/account/recovery');

foreach ([
    '/choose-role.php'      => '/choose-role',
    '/about.php'            => '/about',
    '/trainings-public.php' => '/trainings-public',
    '/privacy.php'          => '/privacy',
    '/terms.php'            => '/terms',
    '/contact.php'          => '/contact',
    '/ecsignuppage.php'     => '/account/ec-signup',
] as $old => $new) {
    Route::get($old, \Illuminate\Routing\RedirectController::class)
        ->defaults('destination', $new)
        ->defaults('status', 301); // GET/HEAD only — never swallow a stray POST from a stale pre-deploy page
}
