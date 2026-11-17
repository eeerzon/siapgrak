<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ResultStatus;
use App\Exceptions\InvalidResultStatusTransition;
use App\Exceptions\NoResultsForRound;
use App\Http\Controllers\Controller;
use App\Http\Requests\Result\ReopenResultRequest;
use App\Http\Requests\Result\UpdatePenaltyRequest;
use App\Models\CompetitionResult;
use App\Models\Event;
use App\Models\JudgingRound;
use App\Services\ResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ResultController extends Controller
{
    public function __construct(protected ResultService $results) {}

    public function calculate(JudgingRound $round): RedirectResponse
    {
        $event = $round->category->event;
        $this->authorize('manageResults', $event);

        try {
            $this->results->calculateForRound($round);
        } catch (InvalidResultStatusTransition $e) {
            throw ValidationException::withMessages(['calculate' => $e->getMessage()]);
        }

        return $this->backToEvent($event, 'Hasil dihitung ulang.');
    }

    public function updatePenalty(UpdatePenaltyRequest $request, CompetitionResult $result): RedirectResponse
    {
        $event = $result->round->category->event;

        $this->results->updatePenalty($result, (float) $request->validated('penalty'));

        return $this->backToEvent($event, 'Penalti diperbarui.');
    }

    public function finalize(JudgingRound $round): RedirectResponse
    {
        $event = $round->category->event;
        $this->authorize('manageResults', $event);

        try {
            $this->results->finalize($round);
        } catch (NoResultsForRound|InvalidResultStatusTransition $e) {
            throw ValidationException::withMessages(['finalize' => $e->getMessage()]);
        }

        return $this->backToEvent($event, 'Hasil round ini difinalisasi.');
    }

    public function publish(JudgingRound $round): RedirectResponse
    {
        $event = $round->category->event;
        $this->authorize('manageResults', $event);

        try {
            $this->results->publish($round);
        } catch (NoResultsForRound|InvalidResultStatusTransition $e) {
            throw ValidationException::withMessages(['publish' => $e->getMessage()]);
        }

        return $this->backToEvent($event, 'Hasil round ini dipublikasikan.');
    }

    public function reopen(ReopenResultRequest $request, JudgingRound $round): RedirectResponse
    {
        $event = $round->category->event;

        $currentlyPublished = CompetitionResult::query()
            ->where('judging_round_id', $round->id)
            ->where('status', ResultStatus::Published)
            ->exists();

        if ($currentlyPublished) {
            $this->authorize('reopenPublishedResult', $event);
        } else {
            $this->authorize('manageResults', $event);
        }

        try {
            $this->results->reopen($round, $request->validated('reason'));
        } catch (NoResultsForRound|InvalidResultStatusTransition $e) {
            throw ValidationException::withMessages(['reopen' => $e->getMessage()]);
        }

        return $this->backToEvent($event, 'Hasil round ini dibuka kembali.');
    }

    protected function backToEvent(Event $event, string $message): RedirectResponse
    {
        return redirect()
            ->route('dashboard.events.show', $event)
            ->with('success', $message);
    }
}
