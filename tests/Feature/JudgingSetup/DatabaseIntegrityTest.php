<?php

namespace Tests\Feature\JudgingSetup;

use App\Models\EventCategory;
use App\Models\Judge;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_cannot_be_hard_deleted_while_it_has_judges(): void
    {
        $judge = Judge::factory()->create();

        $this->expectException(QueryException::class);
        $judge->organization->delete();
    }

    public function test_deleting_a_category_cascades_to_its_judging_rounds(): void
    {
        $category = EventCategory::factory()->create();
        $round = JudgingRound::factory()->create(['event_category_id' => $category->id]);

        $category->delete();

        $this->assertDatabaseMissing('judging_rounds', ['id' => $round->id]);
    }

    public function test_deleting_a_round_cascades_to_its_criteria(): void
    {
        $round = JudgingRound::factory()->create();
        $criterion = JudgingCriteria::factory()->create(['judging_round_id' => $round->id]);

        $round->delete();

        $this->assertDatabaseMissing('judging_criteria', ['id' => $criterion->id]);
    }

    public function test_a_root_criterion_cannot_be_hard_deleted_while_it_has_subcriteria(): void
    {
        $root = JudgingCriteria::factory()->create();
        JudgingCriteria::factory()->create(['judging_round_id' => $root->judging_round_id, 'parent_id' => $root->id]);

        $this->expectException(QueryException::class);
        $root->delete();
    }

    public function test_judge_gets_an_auto_assigned_public_id(): void
    {
        $this->assertStringStartsWith('judge_', Judge::factory()->create()->public_id);
    }
}
