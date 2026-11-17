<?php

namespace App\Services;

use App\Enums\CompetitionEntryStatus;
use App\Enums\ScoreSheetStatus;
use App\Exceptions\IncompleteScoreSheet;
use App\Models\CompetitionEntry;
use App\Models\Judge;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Models\Score;
use App\Models\ScoreSheet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;

/**
 * Judge-facing scoring logic (fr-scoring.md). Every method that takes a
 * Judge re-derives access from judge_assignments — nothing here trusts a
 * caller-supplied "this judge may see this round" assumption, since this is
 * the single most sensitive domain in the app (CLAUDE.md Constitution).
 */
class ScoreSheetService
{
    public function __construct(protected ActivityLogger $activityLogger) {}

    /**
     * Rounds this judge has at least one active assignment in, across every
     * organization/event the linked Judge records belong to.
     *
     * @return Collection<int, JudgingRound>
     */
    public function assignedRounds(Judge $judge): Collection
    {
        return JudgingRound::query()
            ->whereHas('assignments', fn ($query) => $query->where('judge_id', $judge->id))
            ->with('category.event')
            ->orderBy('name')
            ->get()
            ->each(function (JudgingRound $round) use ($judge): void {
                // Progress for the judge's home screen: entries to score vs sheets already sent.
                $round->setAttribute('entries_count', CompetitionEntry::query()
                    ->where('event_category_id', $round->event_category_id)
                    ->whereNotIn('status', [CompetitionEntryStatus::Withdrawn, CompetitionEntryStatus::Disqualified])
                    ->count());
                $round->setAttribute('sent_count', $round->scoreSheets()
                    ->where('judge_id', $judge->id)
                    ->whereIn('status', [ScoreSheetStatus::Submitted, ScoreSheetStatus::Locked])
                    ->count());
            });
    }

    public function assertJudgeAssignedToRound(JudgingRound $round, Judge $judge): void
    {
        abort_unless($round->assignments()->where('judge_id', $judge->id)->exists(), 403);
    }

    /**
     * Union of scoreable (leaf) criteria across every assignment this judge
     * has in the round (fr-scoring.md Keputusan D). A whole-round assignment
     * (judging_criteria_id null) alone covers every leaf criteria.
     *
     * @return BaseCollection<int, JudgingCriteria>
     */
    public function scoreableCriteriaFor(JudgingRound $round, Judge $judge): BaseCollection
    {
        $assignments = $round->assignments()->where('judge_id', $judge->id)->get();
        $allCriteria = $round->criteria()->with('children')->get();

        if ($assignments->contains(fn ($assignment) => $assignment->judging_criteria_id === null)) {
            return $allCriteria->filter(fn ($criteria) => $criteria->isLeaf())->values();
        }

        $assignedIds = $assignments->pluck('judging_criteria_id');

        return $allCriteria
            ->whereIn('id', $assignedIds)
            ->flatMap(fn ($criteria) => $criteria->isLeaf() ? [$criteria] : $criteria->children->all())
            ->unique('id')
            ->values();
    }

    /**
     * Entries a judge can score in this round: every entry in the round's
     * category except withdrawn/disqualified (fr-scoring.md Keputusan E),
     * annotated with this judge's own draft progress.
     *
     * @return Collection<int, CompetitionEntry>
     */
    public function entriesForRound(JudgingRound $round, Judge $judge): Collection
    {
        $this->assertJudgeAssignedToRound($round, $judge);

        $scoreableCriteriaIds = $this->scoreableCriteriaFor($round, $judge)->pluck('id');

        $scoredCounts = Score::query()
            ->select('score_sheets.competition_entry_id', DB::raw('count(*) as scored'))
            ->join('score_sheets', 'score_sheets.id', '=', 'scores.score_sheet_id')
            ->where('score_sheets.judging_round_id', $round->id)
            ->where('score_sheets.judge_id', $judge->id)
            ->whereIn('scores.judging_criteria_id', $scoreableCriteriaIds)
            ->groupBy('score_sheets.competition_entry_id')
            ->pluck('scored', 'score_sheets.competition_entry_id');

        $sheetStatuses = ScoreSheet::query()
            ->where('judging_round_id', $round->id)
            ->where('judge_id', $judge->id)
            ->pluck('status', 'competition_entry_id');

        return CompetitionEntry::query()
            ->where('event_category_id', $round->event_category_id)
            ->whereNotIn('status', [CompetitionEntryStatus::Withdrawn, CompetitionEntryStatus::Disqualified])
            ->with('team')
            ->orderBy('competition_number')
            ->get()
            ->each(function (CompetitionEntry $entry) use ($scoredCounts, $scoreableCriteriaIds, $sheetStatuses) {
                $entry->setAttribute('scored_count', $scoredCounts->get($entry->id, 0));
                $entry->setAttribute('total_criteria', $scoreableCriteriaIds->count());
                // This judge's sheet status (null = not opened yet), so the list can tell drafts from sent sheets.
                $entry->setAttribute('sheet_status', $sheetStatuses->get($entry->id));
            });
    }

    public function getOrCreateScoreSheet(JudgingRound $round, CompetitionEntry $entry, Judge $judge): ScoreSheet
    {
        $this->assertJudgeAssignedToRound($round, $judge);
        abort_unless($entry->event_category_id === $round->event_category_id, 404);

        return ScoreSheet::firstOrCreate([
            'judging_round_id' => $round->id,
            'competition_entry_id' => $entry->id,
            'judge_id' => $judge->id,
        ]);
    }

    /**
     * @param  array<int, array{judging_criteria_id: int|string, score: string|float|int|null, notes?: string|null}>  $scoresInput
     */
    public function saveDraft(ScoreSheet $sheet, array $scoresInput): void
    {
        // Read-only once submitted/locked (fr-score-sheet-lifecycle.md
        // Keputusan D) — the only way back to editable is organizer reopen.
        abort_unless($sheet->status === ScoreSheetStatus::Draft, 403);

        DB::transaction(function () use ($sheet, $scoresInput) {
            foreach ($scoresInput as $row) {
                $criteriaId = (int) $row['judging_criteria_id'];
                $score = $row['score'] ?? null;

                if ($score === null || $score === '') {
                    $sheet->scores()->where('judging_criteria_id', $criteriaId)->delete();

                    continue;
                }

                $sheet->scores()->updateOrCreate(
                    ['judging_criteria_id' => $criteriaId],
                    ['score' => $score, 'notes' => $row['notes'] ?? null],
                );
            }
        });
    }

    /**
     * draft -> submitted (RULE-1, fr-score-sheet-lifecycle.md Keputusan C).
     * Explicit action — never inferred from "every field looks filled".
     *
     * @throws IncompleteScoreSheet
     */
    public function submit(ScoreSheet $sheet): void
    {
        abort_unless($sheet->status === ScoreSheetStatus::Draft, 403);

        $scoreable = $this->scoreableCriteriaFor($sheet->round, $sheet->judge);
        $filledIds = $sheet->scores()->pluck('judging_criteria_id');
        $missing = $scoreable->whereNotIn('id', $filledIds);

        if ($missing->isNotEmpty()) {
            throw new IncompleteScoreSheet($missing);
        }

        $sheet->status = ScoreSheetStatus::Submitted;
        $sheet->submitted_at = now();
        $sheet->save();

        // A judge is not a User (no Auth context) — user_id stays null on
        // purpose, the judge's identity travels in metadata instead
        // (fr-operational-polish.md Sprint 13 audit retrofit).
        $this->activityLogger->log('score_sheet_submitted', $sheet->round->category->event, $sheet, [
            'judge_id' => $sheet->judge_id,
        ]);
    }

    /**
     * submitted -> locked (RULE-2). Never directly from draft — the judge
     * must submit first (Keputusan G).
     */
    public function lock(ScoreSheet $sheet): void
    {
        abort_unless($sheet->status === ScoreSheetStatus::Submitted, 403);

        $sheet->status = ScoreSheetStatus::Locked;
        $sheet->locked_at = now();
        $sheet->save();
    }

    /**
     * submitted/locked -> draft (RULE-3). Scores are kept as-is (Keputusan B)
     * — reopen means "let the judge correct something", not "start over".
     * Who may call this for a locked sheet is gated at the controller/policy
     * layer (Keputusan A), not here.
     */
    public function reopen(ScoreSheet $sheet, ?int $requestedByUserId, string $reason): void
    {
        abort_unless(
            in_array($sheet->status, [ScoreSheetStatus::Submitted, ScoreSheetStatus::Locked], true),
            403,
        );

        DB::transaction(function () use ($sheet, $requestedByUserId, $reason) {
            $sheet->revisions()->create([
                'requested_by' => $requestedByUserId,
                'reason' => $reason,
                'previous_status' => $sheet->status->value,
            ]);

            $sheet->status = ScoreSheetStatus::Draft;
            $sheet->submitted_at = null;
            $sheet->locked_at = null;
            $sheet->revision_count = $sheet->revision_count + 1;
            $sheet->save();
        });

        $this->activityLogger->log('score_sheet_reopened', $sheet->round->category->event, $sheet, [
            'judge_id' => $sheet->judge_id,
            'reason' => $reason,
        ]);
    }
}
