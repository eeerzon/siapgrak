<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Judging\StoreJudgeAssignmentRequest;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\JudgingRound;
use App\Services\JudgingService;
use Illuminate\Http\RedirectResponse;

/**
 * No update endpoint (fr-judge-assignment.md Keputusan F) — change scope by
 * deleting and re-assigning. Gated through EventPolicy::manageJudging, same
 * as rounds/criteria (Keputusan G).
 */
class JudgeAssignmentController extends Controller
{
    public function __construct(protected JudgingService $judging) {}

    public function store(StoreJudgeAssignmentRequest $request, JudgingRound $round): RedirectResponse
    {
        $this->judging->createAssignment($round, $request->validated());

        return $this->backToEvent($round->category->event, 'Juri berhasil ditugaskan.');
    }

    public function destroy(JudgeAssignment $assignment): RedirectResponse
    {
        $event = $assignment->round->category->event;
        $this->authorize('manageJudging', $event);

        $this->judging->deleteAssignment($assignment);

        return $this->backToEvent($event, 'Penugasan juri dihapus.');
    }

    protected function backToEvent(Event $event, string $message): RedirectResponse
    {
        return redirect()
            ->route('dashboard.events.show', $event)
            ->with('success', $message);
    }
}
