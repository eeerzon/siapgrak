<?php

namespace App\Http\Controllers\Dashboard\Content\Judging;

use App\Http\Controllers\Controller;
use App\Models\CompetitionJudge;
use App\Models\CompetitionListing;
use App\Services\CompetitionJudgingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function __construct(private CompetitionJudgingService $judging) {}

    public const TABS = ['peserta' => 'Kategori & Peserta', 'penjurian' => 'Babak & Juri', 'lembar' => 'Lembar Nilai', 'hasil' => 'Hasil'];

    public function show(Request $request, CompetitionListing $competition): View
    {
        $this->authorize('manage', $competition);

        $tab = array_key_exists((string) $this->queryText($request, 'tab'), self::TABS) ? $this->queryText($request, 'tab') : 'peserta';

        $competition->load([
            'categories.participants',
            'categories.rounds.criteria',
            'categories.rounds.judges',
            'categories.rounds.judgings' => fn ($query) => $query
                ->with(['participant', 'judge', 'scores:id,competition_judging_id,competition_criterion_id'])
                ->withCount('scores')
                ->orderBy('competition_participant_id'),
            'categories.rounds.results.participant',
            'categories.rounds.participants',
        ]);

        // Per round: the teams taking part (all of the category in the first round, the chosen ones after),
        // and the previous round for "ambil N teratas". Views read these instead of category->participants.
        foreach ($competition->categories as $category) {
            foreach ($category->rounds as $round) {
                $round->setRelation('category', $category);
                $round->setRelation('entrants', $this->judging->entrants($round));
                $round->setAttribute('is_first_round', $this->judging->isFirstRound($round));
                [$sent, $expected] = $this->judging->sheetProgress($round);
                $round->setAttribute('sheets_sent', $sent);
                $round->setAttribute('sheets_expected', $expected);
                $round->setAttribute('is_complete', $expected > 0 && $sent === $expected);
                $round->setRelation('previousRound', $previous = $this->judging->previousRound($round));
                // Rounds are walked in order, so the previous round already has its is_complete/sheets_* set.

                // "Pilih manual": teams not in the round yet, best first by the previous round's ranking;
                // unranked teams go last, in performance order.
                $previousResults = $previous?->results->keyBy('competition_participant_id') ?? collect();
                $byPreviousRank = [
                    fn ($a, $b) => ($previousResults[$a->id]->rank ?? PHP_INT_MAX) <=> ($previousResults[$b->id]->rank ?? PHP_INT_MAX),
                    fn ($a, $b) => ($a->performance_order ?? PHP_INT_MAX) <=> ($b->performance_order ?? PHP_INT_MAX),
                    fn ($a, $b) => strnatcmp((string) $a->competition_number, (string) $b->competition_number),
                ];
                // Candidates come from the previous round's teams only (e.g. Babak III picks from Babak II's 8).
                $round->setRelation('candidates', ($previous ? $previous->entrants : collect())
                    ->whereNotIn('id', $round->entrants->pluck('id'))
                    ->sortBy($byPreviousRank)
                    ->values());
                // Same order for the chips of teams already in the round (display only; entrants keeps its order elsewhere).
                $round->setRelation('entrantsByRank', $round->entrants->sortBy($byPreviousRank)->values());
                $round->setRelation('previousResults', $previousResults);
                // For the Hasil tab: taking results off the public page is refused once the next round started,
                // and clears the next round's teams otherwise.
                $next = $this->judging->nextRound($round);
                $round->setAttribute('next_round_started', $next !== null && $next->judgings->isNotEmpty());
                $round->setAttribute('next_round_name', $next?->name);
                $round->setAttribute('next_round_has_teams', $next !== null && $next->participants->isNotEmpty());
                $round->setAttribute('team_list_closed_reason', $round->is_first_round ? null : $this->judging->teamListClosedReason($round));
            }
        }

        $judges = CompetitionJudge::ownedBy($request->user())->orderBy('name')->get();

        // Babak & Juri shows one category at a time (?kategori=id); unknown ids fall back to the first category.
        $activeCategory = $competition->categories->firstWhere('id', (int) $this->queryText($request, 'kategori'))
            ?? $competition->categories->first();

        return view('dashboard.content.competitions.judging', compact('competition', 'tab', 'judges', 'activeCategory'));
    }
}
