<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Dashboard\AwardController;
use App\Http\Controllers\Dashboard\CompetitionEntryController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\EventCategoryController;
use App\Http\Controllers\Dashboard\EventController;
use App\Http\Controllers\Dashboard\EventRegistrationController;
use App\Http\Controllers\Dashboard\EventScheduleSlotController;
use App\Http\Controllers\Dashboard\EventStaffController;
use App\Http\Controllers\Dashboard\JudgeAssignmentController;
use App\Http\Controllers\Dashboard\JudgeController;
use App\Http\Controllers\Dashboard\JudgingCriteriaController;
use App\Http\Controllers\Dashboard\JudgingRoundController;
use App\Http\Controllers\Dashboard\OrganizationController;
use App\Http\Controllers\Dashboard\OrganizationMemberController;
use App\Http\Controllers\Dashboard\RegistrationDocumentController;
use App\Http\Controllers\Dashboard\RegistrationImportController;
use App\Http\Controllers\Dashboard\RegistrationMemberController;
use App\Http\Controllers\Dashboard\ResultController;
use App\Http\Controllers\Dashboard\ScoreSheetController;
use App\Http\Controllers\Dashboard\TeamController;
use App\Http\Controllers\Dashboard\TeamMemberController;
use App\Http\Controllers\Judge\AccessController as JudgeAccessController;
use App\Http\Controllers\Judge\DashboardController as JudgeDashboardController;
use App\Http\Controllers\Judge\ScoringController as JudgeScoringController;
use App\Http\Middleware\EnsureJudgeSession;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Named "home" deliberately: Laravel's guest middleware falls back to
    // route('home') when redirecting an already-authenticated user away from
    // /login. If this always redirected to /login regardless of auth state,
    // that fallback would create an infinite redirect loop for logged-in users.
    return auth()->check()
        ? redirect()->route('dashboard.index')
        : redirect()->route('login');
})->name('home');

// Auth — Phase A Sprint 1 prerequisite (see _docs/tasks/tasks.md). No public
// event/registration/judging routes are defined anywhere in this file — out
// of scope for these sprints, see CLAUDE.md and ADR-002.
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');

        Route::resource('organizations', OrganizationController::class)
            ->except(['destroy']);

        Route::prefix('organizations/{organization}/members')
            ->name('organizations.members.')
            ->group(function () {
                Route::get('/', [OrganizationMemberController::class, 'index'])->name('index');
                Route::post('/', [OrganizationMemberController::class, 'store'])->name('store');
                Route::patch('{membership}', [OrganizationMemberController::class, 'update'])->name('update');
                Route::delete('{membership}', [OrganizationMemberController::class, 'destroy'])->name('destroy');
            });

        Route::resource('teams', TeamController::class)
            ->except(['destroy']);

        Route::prefix('teams/{team}/members')
            ->name('teams.members.')
            ->group(function () {
                Route::get('/', [TeamMemberController::class, 'index'])->name('index');
                Route::post('/', [TeamMemberController::class, 'store'])->name('store');
                Route::delete('{member}', [TeamMemberController::class, 'destroy'])->name('destroy');
            });

        // Sprint 3 — Event + Category + Staff. No destroy for events
        // ("cancel" is a status transition, fr-event-category.md Keputusan G).
        Route::resource('events', EventController::class)
            ->except(['destroy']);
        Route::patch('events/{event}/status', [EventController::class, 'updateStatus'])
            ->name('events.status');

        Route::prefix('events/{event}/categories')
            ->name('events.categories.')
            ->group(function () {
                Route::post('/', [EventCategoryController::class, 'store'])->name('store');
                Route::put('{category}', [EventCategoryController::class, 'update'])->name('update');
                Route::delete('{category}', [EventCategoryController::class, 'destroy'])->name('destroy');
            });

        Route::prefix('events/{event}/staff')
            ->name('events.staff.')
            ->group(function () {
                Route::post('/', [EventStaffController::class, 'store'])->name('store');
                Route::patch('{staff}', [EventStaffController::class, 'update'])->name('update');
                Route::delete('{staff}', [EventStaffController::class, 'destroy'])->name('destroy');
            });

        // Sprint 4 — Registration (admin-entry). No destroy ("remove" = status
        // withdrawn, fr-registration.md Keputusan I). Roster & documents nested
        // under {registration} only (registration id is globally unique).
        Route::prefix('events/{event}/registrations')
            ->name('events.registrations.')
            ->group(function () {
                Route::get('/', [EventRegistrationController::class, 'index'])->name('index');
                Route::get('create', [EventRegistrationController::class, 'create'])->name('create');
                Route::post('/', [EventRegistrationController::class, 'store'])->name('store');

                // Sprint 4b — CSV import. Static "import" segment must be
                // registered before the {registration} wildcard below, or
                // Laravel would try to bind "import" as a registration ID.
                Route::prefix('import')
                    ->name('import.')
                    ->group(function () {
                        Route::get('/', [RegistrationImportController::class, 'create'])->name('create');
                        Route::post('/', [RegistrationImportController::class, 'store'])->name('store');
                        Route::get('{token}/map', [RegistrationImportController::class, 'map'])->name('map');
                        Route::post('{token}/map', [RegistrationImportController::class, 'preview'])->name('preview');
                        Route::post('{token}/commit', [RegistrationImportController::class, 'commit'])->name('commit');
                    });

                Route::get('{registration}', [EventRegistrationController::class, 'show'])->name('show');
                Route::patch('{registration}/status', [EventRegistrationController::class, 'updateStatus'])->name('status');
            });

        Route::prefix('registrations/{registration}')
            ->name('registrations.')
            ->group(function () {
                Route::post('members', [RegistrationMemberController::class, 'store'])->name('members.store');
                Route::delete('members/{member}', [RegistrationMemberController::class, 'destroy'])->name('members.destroy');

                Route::post('documents', [RegistrationDocumentController::class, 'store'])->name('documents.store');
                Route::get('documents/{document}', [RegistrationDocumentController::class, 'download'])->name('documents.download');
                Route::delete('documents/{document}', [RegistrationDocumentController::class, 'destroy'])->name('documents.destroy');
            });

        // Sprint 5 — Competition Entry + Number. Entries are created by
        // CompetitionService when a registration is approved, never here
        // (fr-competition-entry.md Keputusan A) — this only assigns numbers
        // and progresses event-day status.
        Route::post('events/{event}/competition/numbers/generate', [CompetitionEntryController::class, 'generateNumbers'])
            ->name('events.competition.numbers.generate');

        Route::prefix('competition-entries/{entry}')
            ->name('competition-entries.')
            ->group(function () {
                Route::patch('number', [CompetitionEntryController::class, 'updateNumber'])->name('number');
                Route::patch('status', [CompetitionEntryController::class, 'updateStatus'])->name('status');
            });

        // Sprint 6 — Schedule. Slots are pure logistics (not competition
        // history) — hard delete is allowed here, unlike every other resource
        // in this file (fr-schedule.md Keputusan B).
        Route::prefix('events/{event}/schedule')
            ->name('events.schedule.')
            ->group(function () {
                Route::post('/', [EventScheduleSlotController::class, 'store'])->name('store');
                Route::put('{slot}', [EventScheduleSlotController::class, 'update'])->name('update');
                Route::delete('{slot}', [EventScheduleSlotController::class, 'destroy'])->name('destroy');
                Route::post('{slot}/move-up', [EventScheduleSlotController::class, 'moveUp'])->name('move-up');
                Route::post('{slot}/move-down', [EventScheduleSlotController::class, 'moveDown'])->name('move-down');
            });

        // Sprint 7 — Judges + Judging Rounds + Criteria. Judges are org-scoped
        // reusable master data (fr-judging-setup.md Keputusan A) but deliberately
        // NOT in the sidebar (Keputusan B) — reached from the event's Penjurian
        // tab. Rounds/Criteria are managed inline there (Keputusan C).
        Route::resource('judges', JudgeController::class)->except(['destroy']);
        Route::patch('judges/{judge}/access-code', [JudgeController::class, 'regenerateAccessCode'])
            ->name('judges.access-code');

        Route::post('events/{event}/categories/{category}/rounds', [JudgingRoundController::class, 'store'])
            ->name('events.categories.rounds.store');

        Route::prefix('judging-rounds/{round}')
            ->name('judging-rounds.')
            ->group(function () {
                Route::put('/', [JudgingRoundController::class, 'update'])->name('update');
                Route::delete('/', [JudgingRoundController::class, 'destroy'])->name('destroy');
                Route::post('criteria', [JudgingCriteriaController::class, 'store'])->name('criteria.store');
                Route::post('assignments', [JudgeAssignmentController::class, 'store'])->name('assignments.store');
                Route::post('results/calculate', [ResultController::class, 'calculate'])->name('results.calculate');
                Route::post('results/finalize', [ResultController::class, 'finalize'])->name('results.finalize');
                Route::post('results/publish', [ResultController::class, 'publish'])->name('results.publish');
                Route::post('results/reopen', [ResultController::class, 'reopen'])->name('results.reopen');
            });

        Route::prefix('judging-criteria/{criterion}')
            ->name('judging-criteria.')
            ->group(function () {
                Route::put('/', [JudgingCriteriaController::class, 'update'])->name('update');
                Route::delete('/', [JudgingCriteriaController::class, 'destroy'])->name('destroy');
            });

        // Sprint 8 — Judge Assignment. Hard delete allowed (no FK from
        // scores/score_sheets to judge_assignments — fr-judge-assignment.md
        // Keputusan E). No update route (Keputusan F).
        Route::delete('judge-assignments/{assignment}', [JudgeAssignmentController::class, 'destroy'])
            ->name('judge-assignments.destroy');

        // Sprint 10 — Score Sheet lifecycle (organizer side: review/lock/
        // reopen). Gated entirely behind manageJudging in the controller —
        // no 'member' access at all (fr-score-sheet-lifecycle.md Keputusan E).
        Route::prefix('score-sheets/{sheet}')
            ->name('score-sheets.')
            ->group(function () {
                Route::get('/', [ScoreSheetController::class, 'show'])->name('show');
                Route::post('lock', [ScoreSheetController::class, 'lock'])->name('lock');
                Route::post('reopen', [ScoreSheetController::class, 'reopen'])->name('reopen');
            });

        // Sprint 11 — Result Calculator + Ranking. Gated entirely behind
        // manageResults (Sprint 12 renamed from manageJudging — same role
        // check, own Phase E method, fr-awards-finalization.md Keputusan D).
        Route::patch('results/{result}/penalty', [ResultController::class, 'updatePenalty'])
            ->name('results.penalty');

        // Sprint 12 — Awards. Managed inline on the event's "Hasil" tab, no
        // dedicated index (pola sama EventCategoryController).
        Route::post('events/{event}/awards', [AwardController::class, 'store'])
            ->name('events.awards.store');
        Route::prefix('awards/{award}')
            ->name('awards.')
            ->group(function () {
                Route::put('/', [AwardController::class, 'update'])->name('update');
                Route::delete('/', [AwardController::class, 'destroy'])->name('destroy');
            });
    });

    // F0 Design Foundation — component gallery. Local-only (never renders in
    // production, see _docs/frontend/frontend-plan.md) + auth-gated as a second
    // layer of defense in case of environment misconfiguration.
    Route::get('dev/ui', function () {
        abort_unless(app()->environment('local'), 404);

        return view('dev.ui');
    })->name('dev.ui');
});

// Sprint 9 — Judge scoring area. Deliberately OUTSIDE the 'auth' middleware
// group above — judges authenticate via a per-judge access code, not the
// organizer Users/session-auth system (fr-scoring.md Keputusan A). Terpisah
// total dari dashboard organizer (coding-standards.md).
Route::prefix('judge')->name('judge.')->group(function () {
    Route::get('access', [JudgeAccessController::class, 'create'])->name('access.create');
    Route::post('access', [JudgeAccessController::class, 'store'])->name('access.store')->middleware('throttle:6,1');
    Route::post('logout', [JudgeAccessController::class, 'destroy'])->name('logout');

    Route::middleware(EnsureJudgeSession::class)->group(function () {
        Route::get('/', [JudgeDashboardController::class, 'index'])->name('dashboard');
        Route::get('rounds/{round}/entries', [JudgeScoringController::class, 'index'])->name('rounds.entries.index');
        Route::get('rounds/{round}/entries/{entry}', [JudgeScoringController::class, 'show'])->name('rounds.entries.show');
        Route::post('rounds/{round}/entries/{entry}/scores', [JudgeScoringController::class, 'store'])->name('rounds.entries.scores.store');
        Route::post('rounds/{round}/entries/{entry}/submit', [JudgeScoringController::class, 'submit'])->name('rounds.entries.submit');
    });
});
