<?php

namespace Tests\Feature\JudgeAssignment;

use App\Models\Judge;
use App\Models\JudgeAssignment;
use App\Models\JudgingRound;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Completes coverage started in ManageAssignmentTest (criteria CASCADE was
 * already tested there) — round CASCADE and judge RESTRICT were never
 * exercised (fr-judge-assignment.md Keputusan E: hard delete of the
 * assignment itself is fine, but judge_id is RESTRICT so a judge with any
 * assignment can't be hard-deleted, even though Judge has no destroy route
 * yet — this is forward-looking defense-in-depth).
 */
class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_the_round_cascades_to_its_assignments(): void
    {
        $assignment = JudgeAssignment::factory()->create();
        $roundId = $assignment->judging_round_id;

        JudgingRound::find($roundId)->delete();

        $this->assertDatabaseMissing('judge_assignments', ['id' => $assignment->id]);
    }

    public function test_judge_cannot_be_hard_deleted_while_assigned(): void
    {
        $assignment = JudgeAssignment::factory()->create();

        $this->expectException(QueryException::class);
        Judge::find($assignment->judge_id)->delete();
    }
}
