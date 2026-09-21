<?php

namespace Tests\Feature\Result;

use App\Models\CompetitionEntry;
use App\Models\CompetitionResult;
use App\Models\EventCategory;
use App\Models\JudgingRound;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies fr-result-calculator.md's Keputusan explicitly: CompetitionResult
 * is pure derived data (CASCADE everywhere) — unlike score_sheets/scores
 * (RESTRICT), deleting its parents must succeed and take the result with it,
 * never block. Never exercised until now (0 coverage before Sprint 13).
 */
class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function makeResult(): CompetitionResult
    {
        return CompetitionResult::factory()->create();
    }

    public function test_deleting_the_category_cascades_to_the_result(): void
    {
        $result = $this->makeResult();
        $categoryId = $result->event_category_id;

        EventCategory::find($categoryId)->delete();

        $this->assertDatabaseMissing('competition_results', ['id' => $result->id]);
    }

    public function test_deleting_the_round_cascades_to_the_result(): void
    {
        $result = $this->makeResult();
        $roundId = $result->judging_round_id;

        JudgingRound::find($roundId)->delete();

        $this->assertDatabaseMissing('competition_results', ['id' => $result->id]);
    }

    public function test_deleting_the_entry_cascades_to_the_result(): void
    {
        $result = $this->makeResult();
        $entryId = $result->competition_entry_id;

        CompetitionEntry::find($entryId)->delete();

        $this->assertDatabaseMissing('competition_results', ['id' => $result->id]);
    }

    public function test_unique_constraint_on_category_round_entry(): void
    {
        $result = $this->makeResult();

        $this->expectException(QueryException::class);
        CompetitionResult::factory()->create([
            'event_category_id' => $result->event_category_id,
            'judging_round_id' => $result->judging_round_id,
            'competition_entry_id' => $result->competition_entry_id,
        ]);
    }
}
