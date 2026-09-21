<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ScoreSheetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScoreSheet\ReopenScoreSheetRequest;
use App\Models\Event;
use App\Models\ScoreSheet;
use App\Services\ScoreSheetService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Organizer-side score sheet review (fr-score-sheet-lifecycle.md). Gated
 * behind manageJudging entirely (Keputusan E) — scores are the most
 * sensitive data domain in the app, so this whole controller is off-limits
 * to plain 'member' role, unlike Round/Criteria which any member can view.
 */
class ScoreSheetController extends Controller
{
    public function __construct(protected ScoreSheetService $scoreSheets) {}

    public function show(ScoreSheet $sheet): View
    {
        $event = $this->eventFor($sheet);
        $this->authorize('manageJudging', $event);

        $sheet->load(['round.category.event', 'entry.team', 'judge', 'scores.criteria', 'revisions.requestedBy']);

        return view('dashboard.score-sheets.show', ['sheet' => $sheet, 'event' => $event]);
    }

    public function lock(ScoreSheet $sheet): RedirectResponse
    {
        $event = $this->eventFor($sheet);
        $this->authorize('manageJudging', $event);

        $this->scoreSheets->lock($sheet);

        return redirect()
            ->route('dashboard.score-sheets.show', $sheet)
            ->with('success', 'Lembar skor dikunci.');
    }

    public function reopen(ReopenScoreSheetRequest $request, ScoreSheet $sheet): RedirectResponse
    {
        $event = $this->eventFor($sheet);

        // Reopening a LOCKED sheet needs Owner specifically (Keputusan A) —
        // a merely-submitted sheet uses the same manageJudging gate as every
        // other Judging action.
        if ($sheet->status === ScoreSheetStatus::Locked) {
            $this->authorize('reopenLockedScoreSheet', $event);
        } else {
            $this->authorize('manageJudging', $event);
        }

        $this->scoreSheets->reopen($sheet, $request->user()->id, $request->validated('reason'));

        return redirect()
            ->route('dashboard.score-sheets.show', $sheet)
            ->with('success', 'Lembar skor dibuka kembali untuk juri.');
    }

    protected function eventFor(ScoreSheet $sheet): Event
    {
        return $sheet->round->category->event;
    }
}
