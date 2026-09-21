<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Judging\StoreJudgingCriteriaRequest;
use App\Http\Requests\Judging\UpdateJudgingCriteriaRequest;
use App\Models\Event;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Services\JudgingService;
use Illuminate\Http\RedirectResponse;

class JudgingCriteriaController extends Controller
{
    public function __construct(protected JudgingService $judging) {}

    public function store(StoreJudgingCriteriaRequest $request, JudgingRound $round): RedirectResponse
    {
        $this->judging->createCriteria($round, $request->validated());

        return $this->backToEvent($round->category->event, 'Kriteria penilaian ditambahkan.');
    }

    public function update(UpdateJudgingCriteriaRequest $request, JudgingCriteria $criterion): RedirectResponse
    {
        $this->judging->updateCriteria($criterion, $request->validated());

        return $this->backToEvent($criterion->round->category->event, 'Kriteria penilaian diperbarui.');
    }

    public function destroy(JudgingCriteria $criterion): RedirectResponse
    {
        $this->authorize('manageJudging', $criterion->round->category->event);

        $event = $criterion->round->category->event;

        // Guard added Sprint 9 — see JudgingRoundController::destroy.
        if ($criterion->scores()->exists()) {
            return $this->backToEvent($event, 'Kriteria tidak bisa dihapus karena sudah ada skor tersimpan.', 'error');
        }

        $this->judging->deleteCriteria($criterion);

        return $this->backToEvent($event, 'Kriteria penilaian dihapus.');
    }

    protected function backToEvent(Event $event, string $message, string $flashKey = 'success'): RedirectResponse
    {
        return redirect()
            ->route('dashboard.events.show', $event)
            ->with($flashKey, $message);
    }
}
