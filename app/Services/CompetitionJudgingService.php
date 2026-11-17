<?php

namespace App\Services;

use App\Enums\ScoreSheetStatus;
use App\Models\CompetitionJudge;
use App\Models\CompetitionJudging;
use App\Models\CompetitionParticipant;
use App\Models\CompetitionParticipantResult;
use App\Models\CompetitionRound;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rules for Info Lomba judging. Status changes only happen here:
 * draft → submitted (judge, all criteria filled) → locked (admin),
 * and submitted/locked → draft only through reopen() with a recorded reason.
 */
class CompetitionJudgingService
{
    /**
     * A round can be judged once it has criteria, every criterion has a weight and the weights total exactly 100%.
     */
    public function isReadyForJudging(CompetitionRound $round): bool
    {
        $criteria = $round->criteria()->get(['weight']);

        return $criteria->isNotEmpty()
            && $criteria->every(fn ($criterion) => $criterion->weight !== null)
            && abs($criteria->sum(fn ($criterion) => (float) $criterion->weight) - 100) < 0.001;
    }

    /**
     * Once any score sheet exists the criteria structure is frozen: no new criteria,
     * no weight or range changes, so every sheet is judged against the same rules.
     */
    public function hasStarted(CompetitionRound $round): bool
    {
        return $round->judgings()->exists();
    }

    public function isAssigned(CompetitionRound $round, CompetitionJudge $judge): bool
    {
        return $round->judges()->whereKey($judge->id)->exists();
    }

    /**
     * The judge's sheet for this participant. With $create = false (just viewing)
     * an unsaved draft is returned, so opening a page never leaves empty sheets behind.
     */
    public function judgingFor(CompetitionRound $round, CompetitionParticipant $participant, CompetitionJudge $judge, bool $create = true): CompetitionJudging
    {
        abort_unless($this->isAssigned($round, $judge), 403);
        abort_unless($participant->competition_category_id === $round->competition_category_id, 404);

        if (! $create) {
            return CompetitionJudging::firstOrNew([
                'competition_round_id' => $round->id,
                'competition_participant_id' => $participant->id,
                'competition_judge_id' => $judge->id,
            ], [
                'competition_listing_id' => $participant->competition_listing_id,
            ]);
        }

        return CompetitionJudging::firstOrCreate([
            'competition_round_id' => $round->id,
            'competition_participant_id' => $participant->id,
            'competition_judge_id' => $judge->id,
        ], [
            'competition_listing_id' => $participant->competition_listing_id,
        ])->refresh();
    }

    /**
     * @param  array<int|string, array{score?: string|float|int|null, notes?: string|null}>  $input  keyed by criterion id
     */
    public function saveDraft(CompetitionJudging $judging, array $input): void
    {
        $this->assertReady($judging->round);
        $this->assertDraft($judging);
        $criterionIds = $judging->round->criteria()->pluck('id');

        DB::transaction(function () use ($judging, $input, $criterionIds): void {
            foreach ($input as $criterionId => $values) {
                if (! $criterionIds->contains((int) $criterionId)) {
                    continue;
                }

                $score = $values['score'] ?? null;

                if ($score === null || $score === '') {
                    // Clearing a field removes the score, so "terisi" always means a real value.
                    $judging->scores()->where('competition_criterion_id', $criterionId)->delete();

                    continue;
                }

                $judging->scores()->updateOrCreate(
                    ['competition_criterion_id' => $criterionId],
                    ['score' => $score, 'notes' => $values['notes'] ?? null],
                );
            }
        });
    }

    public function submit(CompetitionJudging $judging): void
    {
        $this->assertReady($judging->round);
        $this->assertDraft($judging);

        $missing = $judging->round->criteria()
            ->whereNotIn('id', $judging->scores()->select('competition_criterion_id'))
            ->pluck('name');

        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'submit' => 'Nilai belum lengkap: '.$missing->implode(', ').'.',
            ]);
        }

        $judging->forceFill(['status' => ScoreSheetStatus::Submitted, 'submitted_at' => now()])->save();
    }

    public function lock(CompetitionJudging $judging): void
    {
        if ($judging->status !== ScoreSheetStatus::Submitted) {
            throw ValidationException::withMessages(['judging' => 'Hanya lembar yang sudah dikirim yang bisa dikunci.']);
        }

        $judging->forceFill(['status' => ScoreSheetStatus::Locked, 'locked_at' => now()])->save();
    }

    /**
     * Scores are kept: the judge only corrects what was wrong.
     */
    public function reopen(CompetitionJudging $judging, User $user, string $reason): void
    {
        if ($judging->isDraft()) {
            throw ValidationException::withMessages(['judging' => 'Lembar ini masih draft, tidak perlu dibuka ulang.']);
        }

        if ($judging->round->isPublished()) {
            throw ValidationException::withMessages(['judging' => 'Hasil babak ini sudah dipublikasikan. Tarik dulu dari publik sebelum membuka ulang lembar nilai.']);
        }

        DB::transaction(function () use ($judging, $user, $reason): void {
            $judging->revisions()->create([
                'id_user' => $user->id_user,
                'previous_status' => $judging->status->value,
                'reason' => $reason,
            ]);

            $judging->forceFill([
                'status' => ScoreSheetStatus::Draft,
                'submitted_at' => null,
                'locked_at' => null,
                'revision_count' => $judging->revision_count + 1,
            ])->save();
        });
    }

    /**
     * Rebuilds the round's ranking. Per criterion the judges' scores are averaged
     * (only sent/locked sheets count); participants missing any criterion are skipped.
     * weighted = Σ(average × weight / 100), with no weight counting as 100%.
     * Ties share a rank (1, 1, 3).
     *
     * @return int number of ranked participants
     */
    public function calculateResults(CompetitionRound $round): int
    {
        $criteria = $round->criteria()->get();

        $averages = DB::table('competition_scores')
            ->join('competition_judgings', 'competition_judgings.id', '=', 'competition_scores.competition_judging_id')
            ->where('competition_judgings.competition_round_id', $round->id)
            ->whereIn('competition_judgings.status', [ScoreSheetStatus::Submitted->value, ScoreSheetStatus::Locked->value])
            ->groupBy('competition_judgings.competition_participant_id', 'competition_scores.competition_criterion_id')
            ->select(
                'competition_judgings.competition_participant_id as participant_id',
                'competition_scores.competition_criterion_id as criterion_id',
                DB::raw('avg(competition_scores.score) as average'),
            )
            ->get()
            ->groupBy('participant_id');

        $rows = $averages
            ->filter(fn (Collection $scores) => $criteria->every(fn ($criterion) => $scores->contains('criterion_id', $criterion->id)))
            ->map(function (Collection $scores, int $participantId) use ($criteria): array {
                $byCriterion = $scores->keyBy('criterion_id');

                return [
                    'participant_id' => $participantId,
                    'raw' => round($criteria->sum(fn ($criterion) => (float) $byCriterion[$criterion->id]->average), 3),
                    'weighted' => round($criteria->sum(fn ($criterion) => (float) $byCriterion[$criterion->id]->average * (($criterion->weight ?? 100) / 100)), 3),
                ];
            })
            ->sortBy([['weighted', 'desc'], ['raw', 'desc']])
            ->values();

        DB::transaction(function () use ($round, $rows): void {
            $round->results()->delete();
            $listingId = $round->category->competition_listing_id;
            $previous = null;

            foreach ($rows as $index => $row) {
                $rank = $previous && $previous['weighted'] === $row['weighted'] ? $previous['rank'] : $index + 1;

                (new CompetitionParticipantResult)->forceFill([
                    'competition_listing_id' => $listingId,
                    'competition_round_id' => $round->id,
                    'competition_participant_id' => $row['participant_id'],
                    'raw_score' => $row['raw'],
                    'weighted_score' => $row['weighted'],
                    'rank' => $rank,
                ])->save();

                $previous = $row + ['rank' => $rank];
            }
        });

        return $rows->count();
    }

    /**
     * Recalculates first so the public page never shows a stale ranking.
     */
    public function publishResults(CompetitionRound $round): void
    {
        if ($this->calculateResults($round) === 0) {
            throw ValidationException::withMessages(['results' => 'Belum ada peserta dengan nilai lengkap dari juri, jadi belum ada yang bisa dipublikasikan.']);
        }

        $round->forceFill(['results_published_at' => now()])->save();
    }

    public function unpublishResults(CompetitionRound $round): void
    {
        $round->forceFill(['results_published_at' => null])->save();
    }

    public function assertReady(CompetitionRound $round): void
    {
        if (! $this->isReadyForJudging($round)) {
            throw ValidationException::withMessages([
                'submit' => 'Babak ini belum siap dinilai: total bobot kriteria harus tepat 100%. Hubungi panitia.',
            ]);
        }
    }

    private function assertDraft(CompetitionJudging $judging): void
    {
        if (! $judging->isDraft()) {
            throw ValidationException::withMessages(['submit' => 'Nilai sudah dikirim dan tidak bisa diubah lagi.']);
        }
    }
}
