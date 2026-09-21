<?php

namespace App\Http\Controllers\Judge;

use App\Exceptions\IncompleteScoreSheet;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Judge\Concerns\InteractsWithJudgeSession;
use App\Http\Requests\Judge\SaveScoreDraftRequest;
use App\Models\CompetitionEntry;
use App\Models\JudgingRound;
use App\Services\ScoreSheetService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScoringController extends Controller
{
    use InteractsWithJudgeSession;

    public function __construct(protected ScoreSheetService $scoreSheets) {}

    public function index(Request $request, JudgingRound $round): View
    {
        $judge = $this->currentJudge($request);
        $entries = $this->scoreSheets->entriesForRound($round, $judge);

        return view('judge.entries.index', compact('round', 'entries'));
    }

    public function show(Request $request, JudgingRound $round, CompetitionEntry $entry): View
    {
        $judge = $this->currentJudge($request);
        $sheet = $this->scoreSheets->getOrCreateScoreSheet($round, $entry, $judge);
        $criteria = $this->scoreSheets->scoreableCriteriaFor($round, $judge);
        $existingScores = $sheet->scores->keyBy('judging_criteria_id');

        return view('judge.entries.show', compact('round', 'entry', 'sheet', 'criteria', 'existingScores'));
    }

    public function store(SaveScoreDraftRequest $request, JudgingRound $round, CompetitionEntry $entry): RedirectResponse
    {
        $judge = $this->currentJudge($request);
        $sheet = $this->scoreSheets->getOrCreateScoreSheet($round, $entry, $judge);

        $this->scoreSheets->saveDraft($sheet, $request->validated('scores'));

        return redirect()
            ->route('judge.rounds.entries.show', [$round, $entry])
            ->with('success', 'Skor draft tersimpan.');
    }

    public function submit(Request $request, JudgingRound $round, CompetitionEntry $entry): RedirectResponse
    {
        $judge = $this->currentJudge($request);
        $sheet = $this->scoreSheets->getOrCreateScoreSheet($round, $entry, $judge);

        try {
            $this->scoreSheets->submit($sheet);
        } catch (IncompleteScoreSheet $e) {
            // Surfaced as a form-validation error — same pattern as
            // EventController::updateStatus for InvalidEventStatusTransition.
            throw ValidationException::withMessages(['submit' => $e->getMessage()]);
        }

        return redirect()
            ->route('judge.rounds.entries.show', [$round, $entry])
            ->with('success', 'Skor berhasil dikirim.');
    }
}
