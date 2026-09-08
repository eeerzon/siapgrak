<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Dashboard\OrganizationController;
use App\Http\Controllers\Dashboard\OrganizationMemberController;
use App\Http\Controllers\Dashboard\TeamController;
use App\Http\Controllers\Dashboard\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Named "home" deliberately: Laravel's guest middleware falls back to
    // route('home') when redirecting an already-authenticated user away from
    // /login. If this always redirected to /login regardless of auth state,
    // that fallback would create an infinite redirect loop for logged-in users.
    return auth()->check()
        ? redirect()->route('dashboard.organizations.index')
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
    });

    // F0 Design Foundation — component gallery. Local-only (never renders in
    // production, see _docs/frontend/frontend-plan.md) + auth-gated as a second
    // layer of defense in case of environment misconfiguration.
    Route::get('dev/ui', function () {
        abort_unless(app()->environment('local'), 404);

        return view('dev.ui');
    })->name('dev.ui');
});
