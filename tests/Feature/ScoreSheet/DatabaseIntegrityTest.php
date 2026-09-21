<?php

namespace Tests\Feature\ScoreSheet;

use App\Models\CompetitionEntry;
use App\Models\Judge;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Models\Score;
use App\Models\ScoreSheet;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ScoreSheet/Score are competition history (RESTRICT everywhere) — the
 * opposite design from CompetitionResult (fr-scoring.md, fr-result-calculator.md
 * Keputusan). Round/Criteria destroy already have app-level guards for this
 * (Sprint 9/10), but the DB-level RESTRICT itself was never exercised
 * directly (0 coverage before Sprint 13) — this is the defense-in-depth
 * layer that fires if an app-level guard is ever bypassed or missed.
 */
class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSheet(): ScoreSheet
    {
        return ScoreSheet::factory()->create();
    }

    public function test_round_cannot_be_hard_deleted_while_a_score_sheet_exists(): void
    {
        $sheet = $this->makeSheet();

        $this->expectException(QueryException::class);
        JudgingRound::find($sheet->judging_round_id)->delete();
    }

    public function test_entry_cannot_be_hard_deleted_while_a_score_sheet_exists(): void
    {
        $sheet = $this->makeSheet();

        $this->expectException(QueryException::class);
        CompetitionEntry::find($sheet->competition_entry_id)->delete();
    }

    public function test_judge_cannot_be_hard_deleted_while_a_score_sheet_exists(): void
    {
        $sheet = $this->makeSheet();

        $this->expectException(QueryException::class);
        Judge::find($sheet->judge_id)->delete();
    }

    public function test_criteria_cannot_be_hard_deleted_while_a_score_exists(): void
    {
        $criteria = JudgingCriteria::factory()->create();
        $score = Score::factory()->create(['judging_criteria_id' => $criteria->id]);

        $this->expectException(QueryException::class);
        $criteria->delete();

        $this->assertDatabaseHas('scores', ['id' => $score->id]);
    }

    public function test_deleting_the_sheet_cascades_to_its_scores(): void
    {
        $sheet = $this->makeSheet();
        $score = Score::factory()->create(['score_sheet_id' => $sheet->id]);

        $sheet->delete();

        $this->assertDatabaseMissing('scores', ['id' => $score->id]);
    }

    public function test_unique_constraint_on_round_entry_judge(): void
    {
        $sheet = $this->makeSheet();

        $this->expectException(QueryException::class);
        ScoreSheet::factory()->create([
            'judging_round_id' => $sheet->judging_round_id,
            'competition_entry_id' => $sheet->competition_entry_id,
            'judge_id' => $sheet->judge_id,
        ]);
    }
}
