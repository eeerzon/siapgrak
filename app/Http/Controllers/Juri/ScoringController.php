<?php

namespace App\Http\Controllers\Juri;

use App\Enums\ScoreSheetStatus;
use App\Http\Controllers\Controller;
use App\Models\CompetitionJudge;
use App\Models\CompetitionParticipant;
use App\Models\CompetitionRound;
use App\Services\CompetitionJudgingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * What a judge sees: only their own rounds and their own sheets, never other judges' scores.
 */
class ScoringController extends Controller
{
    public function __construct(private CompetitionJudgingService $judging) {}

    public function dashboard(Request $request): View
    {
        $judge = $this->judge($request);

        $rounds = $judge->rounds()
            ->with('category.competition')
            ->withCount('criteria')
            ->get()
            ->each(function (CompetitionRound $round) use ($judge): void {
                $round->setAttribute('ready', $this->judging->isReadyForJudging($round));
                $round->setAttribute('participants_count', CompetitionParticipant::where('competition_category_id', $round->competition_category_id)->count());
                $round->setAttribute('sent_count', $round->judgings()
                    ->where('competition_judge_id', $judge->id)
                    ->whereIn('status', [ScoreSheetStatus::Submitted, ScoreSheetStatus::Locked])
                    ->count());
            });

        return view('juri.dashboard', compact('judge', 'rounds'));
    }

    public function participants(Request $request, CompetitionRound $round): View
    {
        $judge = $this->judge($request);
        abort_unless($this->judging->isAssigned($round, $judge), 403);

        $round->load('category.competition')->loadCount('criteria');
        $sheets = $round->judgings()->where('competition_judge_id', $judge->id)->withCount('scores')->get()->keyBy('competition_participant_id');
        $participants = $round->category->participants()->get();

        $ready = $this->judging->isReadyForJudging($round);

        return view('juri.participants', compact('judge', 'round', 'participants', 'sheets', 'ready'));
    }

    public function show(Request $request, CompetitionRound $round, CompetitionParticipant $participant): View
    {
        $judge = $this->judge($request);
        $judging = $this->judging->judgingFor($round, $participant, $judge, create: false);
        $round->load('category.competition', 'criteria');
        $existingScores = $judging->scores()->get()->keyBy('competition_criterion_id');

        $ready = $this->judging->isReadyForJudging($round);

        return view('juri.score', compact('judge', 'round', 'participant', 'judging', 'existingScores', 'ready'));
    }

    public function save(Request $request, CompetitionRound $round, CompetitionParticipant $participant): RedirectResponse
    {
        $judge = $this->judge($request);
        // Before judgingFor(): a rejected save must not leave a sheet that freezes the criteria.
        $this->judging->assertReady($round);
        $judging = $this->judging->judgingFor($round, $participant, $judge);

        $rules = ['scores' => ['array']];
        foreach ($round->criteria as $criterion) {
            $rules["scores.{$criterion->id}.score"] = ['nullable', 'numeric', 'min:'.(float) $criterion->minimum_score, 'max:'.(float) $criterion->maximum_score];
            $rules["scores.{$criterion->id}.notes"] = ['nullable', 'string', 'max:500'];
        }
        $validated = $request->validate($rules, [
            'scores.*.score.min' => 'Nilai minimal :min.',
            'scores.*.score.max' => 'Nilai maksimal :max.',
            'scores.*.score.numeric' => 'Nilai harus berupa angka.',
        ]);

        $this->judging->saveDraft($judging, $validated['scores'] ?? []);

        // "Kirim Skor Final" posts the same form with kirim=1: save what was typed, then send.
        if ($request->boolean('kirim')) {
            $this->judging->submit($judging->refresh());

            return redirect()->route('juri.score.show', [$round, $participant])->with('success', 'Nilai terkirim ke panitia.');
        }

        return redirect()->route('juri.score.show', [$round, $participant])->with('success', 'Draft nilai tersimpan.');
    }

    public function submit(Request $request, CompetitionRound $round, CompetitionParticipant $participant): RedirectResponse
    {
        $judge = $this->judge($request);
        $this->judging->assertReady($round);
        $judging = $this->judging->judgingFor($round, $participant, $judge);

        $this->judging->submit($judging);

        return redirect()->route('juri.score.show', [$round, $participant])->with('success', 'Nilai terkirim ke panitia.');
    }

    private function judge(Request $request): CompetitionJudge
    {
        return $request->attributes->get('competitionJudge');
    }
}
