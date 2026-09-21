<?php

namespace App\Services;

use App\Enums\CompetitionEntryStatus;
use App\Enums\ResultStatus;
use App\Exceptions\InvalidResultStatusTransition;
use App\Exceptions\NoResultsForRound;
use App\Models\CompetitionEntry;
use App\Models\CompetitionResult;
use App\Models\JudgingRound;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates ResultCalculator against real data — deciding WHICH entries
 * are eligible and persisting the outcome. The calculator itself never
 * touches CompetitionEntry/CompetitionResult write concerns.
 */
class ResultService
{
    public function __construct(protected ResultCalculator $calculator, protected ActivityLogger $activityLogger) {}

    /**
     * Delete-then-rebuild for this round (fr-result-calculator.md Keputusan F)
     * — simplest way to guarantee no stale rows survive (e.g. an entry that
     * just got disqualified), with existing penalty_score preserved.
     *
     * Blocked once the round's results are finalized/published — recalculate
     * must go through reopen() first (fr-awards-finalization.md Keputusan C).
     */
    public function calculateForRound(JudgingRound $round): void
    {
        $current = $this->currentStatus($round);
        if ($current !== null && $current !== ResultStatus::Draft) {
            throw new InvalidResultStatusTransition($current, ResultStatus::Draft);
        }

        DB::transaction(function () use ($round) {
            $existingPenalties = CompetitionResult::query()
                ->where('judging_round_id', $round->id)
                ->pluck('penalty_score', 'competition_entry_id');

            CompetitionResult::query()->where('judging_round_id', $round->id)->delete();

            $entries = CompetitionEntry::query()
                ->where('event_category_id', $round->event_category_id)
                ->whereNotIn('status', [CompetitionEntryStatus::Withdrawn, CompetitionEntryStatus::Disqualified])
                ->get();

            $results = collect();

            foreach ($entries as $entry) {
                $raw = $this->calculator->calculateRawScore($round, $entry);

                if ($raw === null) {
                    continue;
                }

                $weighted = $this->calculator->calculateWeightedScore($round, $entry);
                $penalty = (float) ($existingPenalties[$entry->id] ?? 0);

                $result = new CompetitionResult;
                $result->event_category_id = $round->event_category_id;
                $result->judging_round_id = $round->id;
                $result->competition_entry_id = $entry->id;
                // setAttribute(), not magic assignment — decimal casts store
                // floats fine at runtime, this just sidesteps the editor's
                // overly narrow inferred type for decimal-cast properties.
                $result->setAttribute('raw_score', $raw);
                $result->setAttribute('weighted_score', $weighted);
                $result->setAttribute('penalty_score', $penalty);
                $result->setAttribute('final_score', $this->calculator->calculateFinalScore($weighted, $penalty));
                $result->save();

                $results->push($result);
            }

            $this->applyRanking($results);
        });

        $this->activityLogger->log('result_recalculated', $round->category->event, null, [
            'judging_round_id' => $round->id,
        ]);
    }

    public function updatePenalty(CompetitionResult $result, float $penalty): void
    {
        $result->setAttribute('penalty_score', $penalty);
        $result->setAttribute('final_score', $this->calculator->calculateFinalScore((float) $result->weighted_score, $penalty));
        $result->save();

        $siblings = CompetitionResult::query()
            ->where('judging_round_id', $result->judging_round_id)
            ->with('entry')
            ->get();

        $this->applyRanking($siblings);
    }

    /**
     * draft -> finalized, every result row for the round at once
     * (fr-awards-finalization.md Keputusan A).
     */
    public function finalize(JudgingRound $round): void
    {
        $results = $this->resultsFor($round);
        $current = $this->statusOf($results);

        if ($current !== ResultStatus::Draft) {
            throw new InvalidResultStatusTransition($current, ResultStatus::Finalized);
        }

        foreach ($results as $result) {
            $result->status = ResultStatus::Finalized;
            $result->finalized_at = now();
            $result->save();
        }

        $this->activityLogger->log('result_finalized', $round->category->event, null, ['judging_round_id' => $round->id]);
    }

    /**
     * finalized -> published. Internal status only — no public page appears
     * (ADR-002 still applies, RULE-2).
     */
    public function publish(JudgingRound $round): void
    {
        $results = $this->resultsFor($round);
        $current = $this->statusOf($results);

        if ($current !== ResultStatus::Finalized) {
            throw new InvalidResultStatusTransition($current, ResultStatus::Published);
        }

        foreach ($results as $result) {
            $result->status = ResultStatus::Published;
            $result->save();
        }

        $this->activityLogger->log('result_published', $round->category->event, null, ['judging_round_id' => $round->id]);
    }

    /**
     * finalized/published -> draft. Who may call this for a published round
     * is gated at the controller/policy layer (Keputusan B), not here.
     * Scores/ranking are kept as-is — reopen is a correction, not a restart.
     */
    public function reopen(JudgingRound $round, string $reason): void
    {
        $results = $this->resultsFor($round);
        $current = $this->statusOf($results);

        if (! in_array($current, [ResultStatus::Finalized, ResultStatus::Published], true)) {
            throw new InvalidResultStatusTransition($current, ResultStatus::Draft);
        }

        foreach ($results as $result) {
            $result->status = ResultStatus::Draft;
            $result->finalized_at = null;
            $result->save();
        }

        $this->activityLogger->log('result_reopened', $round->category->event, null, [
            'judging_round_id' => $round->id,
            'previous_status' => $current->value,
            'reason' => $reason,
        ]);
    }

    protected function applyRanking($results): void
    {
        $this->calculator->calculateRanking($results)->each(fn (CompetitionResult $result) => $result->save());
    }

    /**
     * @return Collection<int, CompetitionResult>
     */
    protected function resultsFor(JudgingRound $round)
    {
        $results = CompetitionResult::query()->where('judging_round_id', $round->id)->get();

        if ($results->isEmpty()) {
            throw new NoResultsForRound;
        }

        return $results;
    }

    protected function statusOf($results): ResultStatus
    {
        return $results->first()->status;
    }

    protected function currentStatus(JudgingRound $round): ?ResultStatus
    {
        // Eloquent\Builder::value() still resolves through the model's
        // casts (unlike the plain query builder) — this is already a
        // ResultStatus|null, not a raw string.
        return CompetitionResult::query()->where('judging_round_id', $round->id)->value('status');
    }
}
