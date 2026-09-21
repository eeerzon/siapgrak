<?php

namespace App\Services;

use App\Enums\ScoreSheetStatus;
use App\Models\CompetitionEntry;
use App\Models\CompetitionResult;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Models\Score;
use Illuminate\Support\Collection;

/**
 * Pure calculation — never touches the database beyond reading scores, never
 * decides WHICH entries are eligible (that's ResultService). Named methods
 * match the blueprint contract exactly (chatgpt-dev-checklist §Result
 * Calculator) so ranking/weighting logic never leaks into a controller/view.
 */
class ResultCalculator
{
    /**
     * Leaf criteria never change between entries within one calculateForRound()
     * run, but calculateRawScore/calculateWeightedScore each call
     * resolveCriteriaValues() separately (2 calls per entry) — without this,
     * a round's criteria+children get re-queried from scratch 2x per entry
     * (fr-operational-polish.md N+1 fix). ResultCalculator is resolved once
     * per request via the container, so this instance property is safely
     * scoped to a single calculateForRound() invocation.
     *
     * @var array<int, Collection<int, JudgingCriteria>>
     */
    protected array $leafCriteriaCache = [];

    /**
     * Sum of every leaf criteria's score (fr-result-calculator.md RULE-4).
     * Returns null if the entry is incomplete (RULE-2) — at least one leaf
     * criteria has no submitted/locked score yet.
     */
    public function calculateRawScore(JudgingRound $round, CompetitionEntry $entry): ?float
    {
        $values = $this->resolveCriteriaValues($round, $entry);

        return $values === null ? null : $values->sum('value');
    }

    /**
     * Sum of (score * weight/100) per leaf criteria — null weight counts as
     * 100% (RULE-5, Keputusan I). Null if incomplete, same as raw score.
     */
    public function calculateWeightedScore(JudgingRound $round, CompetitionEntry $entry): ?float
    {
        $values = $this->resolveCriteriaValues($round, $entry);

        if ($values === null) {
            return null;
        }

        return $values->sum(function (array $row) {
            $weight = $row['weight'] !== null ? (float) $row['weight'] : 100.0;

            return $row['value'] * ($weight / 100);
        });
    }

    /**
     * Penalty is never derived — it's whatever an organizer already entered
     * for this entry, preserved across recalculation (Keputusan E).
     */
    public function calculatePenalty(?CompetitionResult $existing): float
    {
        return $existing !== null ? (float) $existing->penalty_score : 0.0;
    }

    public function calculateFinalScore(float $weightedScore, float $penalty): float
    {
        return $weightedScore - $penalty;
    }

    /**
     * Standard competition ranking ("1-2-2-4") — tied final_score gets the
     * same rank, the next distinct score skips ahead by the tie count
     * (RULE-7, Keputusan D). Display order among ties is stabilised by
     * competition_number so re-running this never reorders ties arbitrarily.
     *
     * @param  Collection<int, CompetitionResult>  $results
     * @return Collection<int, CompetitionResult>
     */
    public function calculateRanking(Collection $results): Collection
    {
        $sorted = $results
            ->sortBy([
                fn (CompetitionResult $a, CompetitionResult $b) => (float) $b->final_score <=> (float) $a->final_score,
                fn (CompetitionResult $a, CompetitionResult $b) => ($a->entry?->competition_number ?? '') <=> ($b->entry?->competition_number ?? ''),
            ])
            ->values();

        foreach ($sorted as $result) {
            $higherCount = $sorted->filter(
                fn (CompetitionResult $other) => (float) $other->final_score > (float) $result->final_score,
            )->count();

            $result->rank = $higherCount + 1;
        }

        return $sorted;
    }

    /**
     * Per leaf criteria: the value to use (average across judges if more
     * than one scored it — Keputusan C) and its weight. Returns null the
     * moment any leaf criteria has no submitted/locked score at all.
     *
     * @return Collection<int, array{value: float, weight: string|float|null}>|null
     */
    protected function resolveCriteriaValues(JudgingRound $round, CompetitionEntry $entry): ?Collection
    {
        $leafCriteria = $this->leafCriteriaCache[$round->id] ??= $round->criteria()
            ->with('children')
            ->get()
            ->filter(fn ($criteria) => $criteria->isLeaf());

        $rows = collect();

        foreach ($leafCriteria as $criteria) {
            $scores = Score::query()
                ->where('judging_criteria_id', $criteria->id)
                ->whereHas('scoreSheet', function ($query) use ($round, $entry) {
                    $query->where('judging_round_id', $round->id)
                        ->where('competition_entry_id', $entry->id)
                        ->whereIn('status', [ScoreSheetStatus::Submitted, ScoreSheetStatus::Locked]);
                })
                ->pluck('score');

            if ($scores->isEmpty()) {
                return null;
            }

            $rows->push(['value' => (float) $scores->avg(), 'weight' => $criteria->weight]);
        }

        return $rows;
    }
}
