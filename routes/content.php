<?php

use App\Http\Controllers\Dashboard\Content\AgendaItemController;
use App\Http\Controllers\Dashboard\Content\CommunityPostController;
use App\Http\Controllers\Dashboard\Content\CompetitionListingController;
use App\Http\Controllers\Dashboard\Content\Judging\JudgeController;
use App\Http\Controllers\Dashboard\Content\Judging\ScoringController;
use App\Http\Controllers\Dashboard\Content\Judging\SetupController;
use App\Http\Controllers\Dashboard\Content\Judging\WorkspaceController;
use App\Http\Controllers\Juri\AccessController as JuriAccessController;
use App\Http\Controllers\Juri\ScoringController as JuriScoringController;
use App\Http\Controllers\Site\AgendaController as SiteAgendaController;
use App\Http\Controllers\Site\CommunityController as SiteCommunityController;
use App\Http\Controllers\Site\CompetitionController as SiteCompetitionController;
use App\Http\Controllers\Site\CompetitionResultController as SiteCompetitionResultController;
use App\Http\Middleware\EnsureCompetitionJudgeSession;
use App\Http\Middleware\EnsurePlatformAdmin;
use Illuminate\Support\Facades\Route;

// Public site (ADR-003). Read-only, and only ever serves published content.
Route::name('site.')->group(function () {
    Route::get('info-lomba', [SiteCompetitionController::class, 'index'])->name('competitions.index');
    Route::get('info-lomba/{slug}', [SiteCompetitionController::class, 'show'])->name('competitions.show');
    Route::get('info-lomba/{slug}/hasil', [SiteCompetitionResultController::class, 'show'])->name('competitions.results');

    Route::get('agenda', [SiteAgendaController::class, 'index'])->name('agenda.index');
    Route::get('agenda/{slug}', [SiteAgendaController::class, 'show'])->name('agenda.show');

    Route::get('kabar', [SiteCommunityController::class, 'index'])->name('community.index');
    Route::get('kabar/{slug}', [SiteCommunityController::class, 'show'])->name('community.show');
});

// Platform admin panel. Content is global, not owned by an organization, so
// access is by platform role instead of a tenant Policy (fr-public-content.md).
Route::middleware(['auth', EnsurePlatformAdmin::class])
    ->prefix('dashboard/content')
    ->name('dashboard.content.')
    ->group(function () {
        Route::resource('competitions', CompetitionListingController::class)
            ->parameters(['competitions' => 'competition']);

        // Judging for one Info Lomba (separate tables from the organizer event module).
        Route::prefix('competitions/{competition}/judging')
            ->name('competitions.judging.')
            ->group(function () {
                Route::get('/', [WorkspaceController::class, 'show'])->name('show');
                Route::post('categories', [SetupController::class, 'storeCategory'])->name('categories.store');
                Route::delete('categories/{category}', [SetupController::class, 'destroyCategory'])->name('categories.destroy');
                Route::post('participants', [SetupController::class, 'storeParticipant'])->name('participants.store');
                Route::put('participants/{participant}', [SetupController::class, 'updateParticipant'])->name('participants.update');
                Route::delete('participants/{participant}', [SetupController::class, 'destroyParticipant'])->name('participants.destroy');
                Route::post('rounds', [SetupController::class, 'storeRound'])->name('rounds.store');
                Route::delete('rounds/{round}', [SetupController::class, 'destroyRound'])->name('rounds.destroy');
                Route::post('rounds/{round}/criteria', [SetupController::class, 'storeCriterion'])->name('criteria.store');
                Route::put('criteria/{criterion}', [SetupController::class, 'updateCriterion'])->name('criteria.update');
                Route::delete('criteria/{criterion}', [SetupController::class, 'destroyCriterion'])->name('criteria.destroy');
                Route::post('rounds/{round}/judges', [SetupController::class, 'assignJudge'])->name('judges.assign');
                Route::delete('rounds/{round}/judges/{judge}', [SetupController::class, 'unassignJudge'])->name('judges.unassign');
                Route::post('judgings/{judging}/lock', [ScoringController::class, 'lock'])->name('judgings.lock');
                Route::post('judgings/{judging}/reopen', [ScoringController::class, 'reopen'])->name('judgings.reopen');
                Route::post('rounds/{round}/results', [ScoringController::class, 'calculate'])->name('results.calculate');
                Route::post('rounds/{round}/publish', [ScoringController::class, 'publish'])->name('results.publish');
                Route::delete('rounds/{round}/publish', [ScoringController::class, 'unpublish'])->name('results.unpublish');
            });

        Route::get('judges', [JudgeController::class, 'index'])->name('judges.index');
        Route::post('judges', [JudgeController::class, 'store'])->name('judges.store');
        Route::put('judges/{judge}', [JudgeController::class, 'update'])->name('judges.update');
        Route::patch('judges/{judge}/access-code', [JudgeController::class, 'regenerateAccessCode'])->name('judges.access-code');

        Route::resource('agenda', AgendaItemController::class)
            ->except(['show'])
            ->parameters(['agenda' => 'agendaItem']);

        Route::resource('community', CommunityPostController::class)
            ->except(['show'])
            ->parameters(['community' => 'communityPost']);
    });

// Judge panel for Info Lomba judging. Judges log in with an access code, not a
// user account; the organizer-event panel at /judge stays separate.
Route::prefix('juri')->name('juri.')->group(function () {
    Route::get('masuk', [JuriAccessController::class, 'create'])->name('access.create');
    Route::post('masuk', [JuriAccessController::class, 'store'])->name('access.store')->middleware('throttle:6,1');
    Route::post('keluar', [JuriAccessController::class, 'destroy'])->name('logout');

    Route::middleware(EnsureCompetitionJudgeSession::class)->group(function () {
        Route::get('/', [JuriScoringController::class, 'dashboard'])->name('dashboard');
        Route::get('babak/{round}', [JuriScoringController::class, 'participants'])->name('round');
        Route::get('babak/{round}/peserta/{participant}', [JuriScoringController::class, 'show'])->name('score.show');
        Route::post('babak/{round}/peserta/{participant}', [JuriScoringController::class, 'save'])->name('score.save');
        Route::post('babak/{round}/peserta/{participant}/kirim', [JuriScoringController::class, 'submit'])->name('score.submit');
    });
});
