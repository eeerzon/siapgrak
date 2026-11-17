<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Judging\StoreJudgingRoundRequest;
use App\Http\Requests\Judging\UpdateJudgingRoundRequest;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\JudgingRound;
use App\Services\JudgingService;
use Illuminate\Http\RedirectResponse;

class JudgingRoundController extends Controller
{
    public function __construct(protected JudgingService $judging) {}

    public function store(StoreJudgingRoundRequest $request, Event $event, EventCategory $category): RedirectResponse
    {
        abort_unless($category->event_id === $event->id, 404);

        $this->judging->createRound($category, $request->validated());

        return $this->backToEvent($event, 'Round penilaian ditambahkan.');
    }

    public function update(UpdateJudgingRoundRequest $request, JudgingRound $round): RedirectResponse
    {
        $this->judging->updateRound($round, $request->validated());

        return $this->backToEvent($round->category->event, 'Round penilaian diperbarui.');
    }

    public function destroy(JudgingRound $round): RedirectResponse
    {
        $this->authorize('manageJudging', $round->category->event);

        $event = $round->category->event;

        if ($round->scoreSheets()->exists()) {
            return $this->backToEvent($event, 'Round tidak bisa dihapus karena sudah ada skor tersimpan.', 'error');
        }

        $this->judging->deleteRound($round);

        return $this->backToEvent($event, 'Round penilaian dihapus.');
    }

    protected function backToEvent(Event $event, string $message, string $flashKey = 'success'): RedirectResponse
    {
        return redirect()
            ->route('dashboard.events.show', $event)
            ->with($flashKey, $message);
    }
}
