<?php

namespace Tests\Feature\Result;

use App\Enums\CompetitionEntryStatus;
use App\Enums\ScoreSheetStatus;
use App\Models\CompetitionEntry;
use App\Models\CompetitionResult;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Judge;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\Score;
use App\Models\ScoreSheet;
use App\Services\ResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculationTest extends TestCase
{
    use RefreshDatabase;

    protected JudgingRound $round;

    protected EventCategory $category;

    protected JudgingCriteria $pbb;

    protected JudgingCriteria $variasi;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $organization->id]);
        $this->category = EventCategory::factory()->create(['event_id' => $event->id]);
        $this->round = JudgingRound::factory()->create(['event_category_id' => $this->category->id]);

        $this->pbb = JudgingCriteria::factory()->create([
            'judging_round_id' => $this->round->id,
            'weight' => 60,
            'minimum_score' => 0,
            'maximum_score' => 100,
        ]);
        $this->variasi = JudgingCriteria::factory()->create([
            'judging_round_id' => $this->round->id,
            'weight' => null,
            'minimum_score' => 0,
            'maximum_score' => 50,
        ]);
    }

    protected function entryFor(EventCategory $category): CompetitionEntry
    {
        return CompetitionEntry::factory()->create([
            'event_id' => $category->event_id,
            'event_category_id' => $category->id,
        ]);
    }

    protected function submittedScore(CompetitionEntry $entry, JudgingCriteria $criteria, float $score, ?Judge $judge = null): void
    {
        $judge ??= Judge::factory()->create();

        $sheet = ScoreSheet::factory()->create([
            'judging_round_id' => $this->round->id,
            'competition_entry_id' => $entry->id,
            'judge_id' => $judge->id,
        ]);
        $sheet->status = ScoreSheetStatus::Submitted;
        $sheet->submitted_at = now();
        $sheet->save();

        Score::factory()->create(['score_sheet_id' => $sheet->id, 'judging_criteria_id' => $criteria->id, 'score' => $score]);
    }

    public function test_raw_score_is_the_sum_of_every_leaf_criteria(): void
    {
        $entry = $this->entryFor($this->category);
        $this->submittedScore($entry, $this->pbb, 80);
        $this->submittedScore($entry, $this->variasi, 30);

        app(ResultService::class)->calculateForRound($this->round);

        $result = CompetitionResult::where('competition_entry_id', $entry->id)->firstOrFail();
        $this->assertSame('110.000', $result->raw_score);
    }

    public function test_weighted_score_applies_criteria_weight_and_treats_null_weight_as_100_percent(): void
    {
        $entry = $this->entryFor($this->category);
        $this->submittedScore($entry, $this->pbb, 80); // weight 60% -> 48
        $this->submittedScore($entry, $this->variasi, 30); // weight null -> 100% -> 30

        app(ResultService::class)->calculateForRound($this->round);

        $result = CompetitionResult::where('competition_entry_id', $entry->id)->firstOrFail();
        $this->assertSame('78.000', $result->weighted_score);
    }

    public function test_criteria_scored_by_multiple_judges_is_averaged(): void
    {
        $entry = $this->entryFor($this->category);
        $this->submittedScore($entry, $this->pbb, 80);
        $this->submittedScore($entry, $this->pbb, 100);
        $this->submittedScore($entry, $this->variasi, 20);

        app(ResultService::class)->calculateForRound($this->round);

        $result = CompetitionResult::where('competition_entry_id', $entry->id)->firstOrFail();
        // pbb averaged = 90 -> weighted 60% = 54; variasi 20 -> weighted 100% = 20
        $this->assertSame('74.000', $result->weighted_score);
    }

    public function test_entry_missing_a_criteria_score_is_skipped_entirely(): void
    {
        $entry = $this->entryFor($this->category);
        $this->submittedScore($entry, $this->pbb, 80);
        // Variasi never scored.

        app(ResultService::class)->calculateForRound($this->round);

        $this->assertDatabaseMissing('competition_results', ['competition_entry_id' => $entry->id]);
    }

    public function test_draft_score_sheet_does_not_count(): void
    {
        $entry = $this->entryFor($this->category);
        $this->submittedScore($entry, $this->pbb, 80);

        $judge = Judge::factory()->create();
        $sheet = ScoreSheet::factory()->create([
            'judging_round_id' => $this->round->id,
            'competition_entry_id' => $entry->id,
            'judge_id' => $judge->id,
        ]);
        Score::factory()->create(['score_sheet_id' => $sheet->id, 'judging_criteria_id' => $this->variasi->id, 'score' => 30]);
        // Sheet left in draft status on purpose.

        app(ResultService::class)->calculateForRound($this->round);

        $this->assertDatabaseMissing('competition_results', ['competition_entry_id' => $entry->id]);
    }

    public function test_locked_score_sheet_still_counts(): void
    {
        $entry = $this->entryFor($this->category);
        $judge = Judge::factory()->create();
        $sheet = ScoreSheet::factory()->create([
            'judging_round_id' => $this->round->id,
            'competition_entry_id' => $entry->id,
            'judge_id' => $judge->id,
        ]);
        $sheet->status = ScoreSheetStatus::Locked;
        $sheet->submitted_at = now();
        $sheet->locked_at = now();
        $sheet->save();
        Score::factory()->create(['score_sheet_id' => $sheet->id, 'judging_criteria_id' => $this->pbb->id, 'score' => 80]);
        $this->submittedScore($entry, $this->variasi, 30);

        app(ResultService::class)->calculateForRound($this->round);

        $this->assertDatabaseHas('competition_results', ['competition_entry_id' => $entry->id]);
    }

    public function test_withdrawn_and_disqualified_entries_never_get_a_result(): void
    {
        $withdrawn = $this->entryFor($this->category);
        $withdrawn->status = CompetitionEntryStatus::Withdrawn;
        $withdrawn->save();
        $this->submittedScore($withdrawn, $this->pbb, 80);
        $this->submittedScore($withdrawn, $this->variasi, 30);

        $disqualified = $this->entryFor($this->category);
        $disqualified->status = CompetitionEntryStatus::Disqualified;
        $disqualified->save();
        $this->submittedScore($disqualified, $this->pbb, 80);
        $this->submittedScore($disqualified, $this->variasi, 30);

        app(ResultService::class)->calculateForRound($this->round);

        $this->assertDatabaseMissing('competition_results', ['competition_entry_id' => $withdrawn->id]);
        $this->assertDatabaseMissing('competition_results', ['competition_entry_id' => $disqualified->id]);
    }

    public function test_final_score_subtracts_penalty(): void
    {
        $entry = $this->entryFor($this->category);
        $this->submittedScore($entry, $this->pbb, 80);
        $this->submittedScore($entry, $this->variasi, 30);

        app(ResultService::class)->calculateForRound($this->round);
        $result = CompetitionResult::where('competition_entry_id', $entry->id)->firstOrFail();

        app(ResultService::class)->updatePenalty($result, 10);

        $result->refresh();
        $this->assertSame('68.000', $result->final_score);
    }
}
