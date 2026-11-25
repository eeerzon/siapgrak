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
     * The first round of a category (lowest display order) is open to every team in it.
     */
    public function isFirstRound(CompetitionRound $round): bool
    {
        return $round->category->rounds->first()?->is($round) ?? true;
    }

    /**
     * The round right before this one in the same category, or null for the first round.
     */
    public function previousRound(CompetitionRound $round): ?CompetitionRound
    {
        $rounds = $round->category->rounds->values();
        $index = $rounds->search(fn (CompetitionRound $item) => $item->is($round));

        return $index ? $rounds[$index - 1] : null;
    }

    /**
     * The round right after this one in the same category, or null for the last round.
     */
    public function nextRound(CompetitionRound $round): ?CompetitionRound
    {
        $rounds = $round->category->rounds->values();
        $index = $rounds->search(fn (CompetitionRound $item) => $item->is($round));

        return $index === false ? null : $rounds->get($index + 1);
    }

    /**
     * Why teams can no longer be added to this (later) round, or null when they can:
     * - the next round has started judging: its teams came from this round's ranking, so it is frozen;
     * - this round's results are public: judges could not score a new team anyway (unpublish first).
     */
    public function teamListClosedReason(CompetitionRound $round): ?string
    {
        $next = $this->nextRound($round);

        if ($next && $next->judgings->isNotEmpty()) {
            return "{$next->name} sudah mulai dinilai, jadi peserta {$round->name} tidak bisa ditambah lagi.";
        }

        if ($round->isPublished()) {
            return "Hasil {$round->name} sudah dipublikasikan. Tarik dulu dari publik di tab Hasil kalau perlu menambah peserta, supaya juri bisa menilai peserta baru.";
        }

        return null;
    }

    /**
     * [sent, expected] score sheets of the round: expected = teams × assigned judges,
     * sent = submitted/locked sheets of those judges for those teams (drafts never count).
     *
     * @return array{0: int, 1: int}
     */
    public function sheetProgress(CompetitionRound $round): array
    {
        $judgeIds = $round->judges->pluck('id');
        $teamIds = $this->entrants($round)->pluck('id');

        $sent = $round->judgings
            ->filter(fn (CompetitionJudging $sheet) => ! $sheet->isDraft()
                && $judgeIds->contains($sheet->competition_judge_id)
                && $teamIds->contains($sheet->competition_participant_id))
            ->unique(fn (CompetitionJudging $sheet) => $sheet->competition_judge_id.'-'.$sheet->competition_participant_id)
            ->count();

        return [$sent, $judgeIds->count() * $teamIds->count()];
    }

    /**
     * Judging of the round is finished: it has judges and teams, and every judge has sent
     * a complete sheet for every team. Reopening a sheet makes it unfinished again.
     */
    public function isComplete(CompetitionRound $round): bool
    {
        [$sent, $expected] = $this->sheetProgress($round);

        return $expected > 0 && $sent === $expected;
    }

    /**
     * Teams taking part in the round: every team of the category in the first round,
     * afterwards only the teams the admin moved on (competition_round_participants).
     * Uses loaded relations when present, so the admin workspace stays at a few queries.
     *
     * @return Collection<int, CompetitionParticipant>
     */
    public function entrants(CompetitionRound $round): Collection
    {
        // The category filter is a safety net: a later round never lists a team of another category.
        return $this->isFirstRound($round)
            ? $round->category->participants
            : $round->participants->where('competition_category_id', $round->competition_category_id)->values();
    }

    /**
     * The judge's sheet for this participant. With $create = false (just viewing)
     * an unsaved draft is returned, so opening a page never leaves empty sheets behind.
     */
    public function judgingFor(CompetitionRound $round, CompetitionParticipant $participant, CompetitionJudge $judge, bool $create = true): CompetitionJudging
    {
        abort_unless($this->isAssigned($round, $judge), 403);
        abort_unless($participant->competition_category_id === $round->competition_category_id, 404);
        // A team that did not make this round cannot be scored in it.
        abort_unless($this->entrants($round)->contains('id', $participant->id), 404);

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
        $this->assertOpen($judging->round);
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
        $this->assertOpen($judging->round);
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
     * Rebuilds the round's ranking. A participant is ranked only once EVERY judge assigned
     * to the round has sent (or locked) their sheet, so all ranked participants are averaged
     * over the same judges. Per criterion the judges' scores are averaged; drafts never count.
     * weighted = Σ(average × weight / 100), with no weight counting as 100%.
     * Ties share a rank (1, 1, 3).
     *
     * @return int number of ranked participants
     */
    public function calculateResults(CompetitionRound $round): int
    {
        $criteria = $round->criteria()->get();
        $judgeIds = $round->judges()->pluck('competition_judges.id');

        // Teams of this round with a sent/locked sheet from every assigned judge.
        $fullyJudged = $judgeIds->isEmpty() ? collect() : CompetitionJudging::query()
            ->where('competition_round_id', $round->id)
            ->whereIn('competition_participant_id', $this->entrants($round)->pluck('id'))
            ->whereIn('competition_judge_id', $judgeIds)
            ->whereIn('status', [ScoreSheetStatus::Submitted->value, ScoreSheetStatus::Locked->value])
            ->groupBy('competition_participant_id')
            ->havingRaw('count(distinct competition_judge_id) = ?', [$judgeIds->count()])
            ->pluck('competition_participant_id');

        $averages = DB::table('competition_scores')
            ->join('competition_judgings', 'competition_judgings.id', '=', 'competition_scores.competition_judging_id')
            ->where('competition_judgings.competition_round_id', $round->id)
            ->whereIn('competition_judgings.competition_participant_id', $fullyJudged)
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
    /**
     * @return int number of ranked participants
     */
    public function publishResults(CompetitionRound $round): int
    {
        if (($count = $this->calculateResults($round)) === 0) {
            throw ValidationException::withMessages(['results' => 'Belum ada peserta yang sudah dinilai semua juri, jadi belum ada yang bisa dipublikasikan.']);
        }

        $round->forceFill(['results_published_at' => now()])->save();

        return $count;
    }

    /**
     * Takes the round's results off the public page. Refused once the next round has started judging
     * (its teams came from this official ranking, which must not change any more). Teams already
     * picked for later rounds that have not started are cleared, since the ranking may now change.
     *
     * @return list<string> names of the later rounds whose team list was cleared
     */
    public function unpublishResults(CompetitionRound $round): array
    {
        $next = $this->nextRound($round);

        if ($next && $next->judgings()->exists()) {
            throw ValidationException::withMessages([
                'results' => "{$next->name} sudah mulai dinilai memakai peringkat {$round->name}, jadi hasil {$round->name} tidak bisa ditarik lagi.",
            ]);
        }

        return DB::transaction(function () use ($round): array {
            $round->forceFill(['results_published_at' => null])->save();

            return $this->clearLaterRoundTeams($round);
        });
    }

    /**
     * Empties the team lists of the rounds after this one that have not started judging
     * (they were picked from this round's ranking, which is changing). No sheet is touched.
     *
     * @return list<string> names of the rounds that were cleared
     */
    public function clearLaterRoundTeams(CompetitionRound $round): array
    {
        $later = $round->category->rounds()->get()
            ->filter(fn (CompetitionRound $item) => $item->display_order > $round->display_order
                || ($item->display_order === $round->display_order && $item->id > $round->id))
            ->filter(fn (CompetitionRound $item) => $item->participants()->exists() && ! $item->judgings()->exists());

        $later->each(fn (CompetitionRound $item) => $item->participants()->detach());

        return $later->pluck('name')->values()->all();
    }

    public function assertReady(CompetitionRound $round): void
    {
        if (! $this->isReadyForJudging($round)) {
            throw ValidationException::withMessages([
                'submit' => 'Babak ini belum siap dinilai: total bobot kriteria harus tepat 100%. Hubungi panitia.',
            ]);
        }
    }

    /**
     * Judges can score only while the round is ready and its results are not public yet.
     * Otherwise a late sheet would change the public per-judge scores but not the published ranking.
     */
    public function assertOpen(CompetitionRound $round): void
    {
        $this->assertReady($round);

        if ($round->isPublished()) {
            throw ValidationException::withMessages([
                'submit' => 'Hasil babak ini sudah diumumkan, jadi penilaian sudah ditutup. Hubungi panitia.',
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
