<?php

namespace Tests\Feature\Result;

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

class RankingTest extends TestCase
{
    use RefreshDatabase;

    protected JudgingRound $round;

    protected EventCategory $category;

    protected JudgingCriteria $criteria;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $organization->id]);
        $this->category = EventCategory::factory()->create(['event_id' => $event->id]);
        $this->round = JudgingRound::factory()->create(['event_category_id' => $this->category->id]);
        $this->criteria = JudgingCriteria::factory()->create([
            'judging_round_id' => $this->round->id,
            'minimum_score' => 0,
            'maximum_score' => 100,
        ]);
    }

    protected function entryScoring(string $competitionNumber, float $score): CompetitionEntry
    {
        $entry = CompetitionEntry::factory()->create([
            'event_id' => $this->category->event_id,
            'event_category_id' => $this->category->id,
        ]);
        $entry->competition_number = $competitionNumber;
        $entry->save();

        $judge = Judge::factory()->create();
        $sheet = ScoreSheet::factory()->create([
            'judging_round_id' => $this->round->id,
            'competition_entry_id' => $entry->id,
            'judge_id' => $judge->id,
        ]);
        $sheet->status = ScoreSheetStatus::Submitted;
        $sheet->submitted_at = now();
        $sheet->save();
        Score::factory()->create(['score_sheet_id' => $sheet->id, 'judging_criteria_id' => $this->criteria->id, 'score' => $score]);

        return $entry;
    }

    public function test_unique_scores_are_ranked_sequentially(): void
    {
        $first = $this->entryScoring('A01', 90);
        $second = $this->entryScoring('A02', 80);
        $third = $this->entryScoring('A03', 70);

        app(ResultService::class)->calculateForRound($this->round);

        $this->assertSame(1, CompetitionResult::where('competition_entry_id', $first->id)->firstOrFail()->rank);
        $this->assertSame(2, CompetitionResult::where('competition_entry_id', $second->id)->firstOrFail()->rank);
        $this->assertSame(3, CompetitionResult::where('competition_entry_id', $third->id)->firstOrFail()->rank);
    }

    public function test_tied_scores_share_rank_and_the_next_rank_skips_ahead(): void
    {
        $tiedA = $this->entryScoring('A01', 90);
        $tiedB = $this->entryScoring('A02', 90);
        $third = $this->entryScoring('A03', 70);

        app(ResultService::class)->calculateForRound($this->round);

        $this->assertSame(1, CompetitionResult::where('competition_entry_id', $tiedA->id)->firstOrFail()->rank);
        $this->assertSame(1, CompetitionResult::where('competition_entry_id', $tiedB->id)->firstOrFail()->rank);
        // Standard competition ranking: 1-1-3, not dense 1-1-2.
        $this->assertSame(3, CompetitionResult::where('competition_entry_id', $third->id)->firstOrFail()->rank);
    }

    public function test_display_order_among_ties_is_stable_by_competition_number(): void
    {
        $this->entryScoring('A02', 90);
        $this->entryScoring('A01', 90);

        app(ResultService::class)->calculateForRound($this->round);

        $ordered = $this->round->results()->get();
        $this->assertSame('A01', $ordered->first()->entry->competition_number);
        $this->assertSame('A02', $ordered->last()->entry->competition_number);
    }
}
