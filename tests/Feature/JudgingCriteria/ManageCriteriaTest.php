<?php

namespace Tests\Feature\JudgingCriteria;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Judge;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\Score;
use App\Models\ScoreSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageCriteriaTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected JudgingRound $round;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $category = EventCategory::factory()->create(['event_id' => $event->id]);
        $this->round = JudgingRound::factory()->create(['event_category_id' => $category->id]);
    }

    public function test_owner_can_add_a_root_criterion(): void
    {
        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.criteria.store', $this->round), [
                'name' => 'PBB',
                'minimum_score' => 0,
                'maximum_score' => 100,
            ])
            ->assertRedirect(route('dashboard.events.show', $this->round->category->event));

        $this->assertDatabaseHas('judging_criteria', ['judging_round_id' => $this->round->id, 'name' => 'PBB', 'parent_id' => null]);
    }

    public function test_a_subcriterion_can_be_added_under_a_root_criterion(): void
    {
        $root = JudgingCriteria::factory()->create(['judging_round_id' => $this->round->id, 'name' => 'PBB']);

        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.criteria.store', $this->round), [
            'name' => 'Gerakan Dasar',
            'minimum_score' => 0,
            'maximum_score' => 20,
            'parent_id' => $root->id,
        ]);

        $this->assertDatabaseHas('judging_criteria', ['name' => 'Gerakan Dasar', 'parent_id' => $root->id]);
    }

    public function test_a_subcriterion_of_a_subcriterion_is_rejected_depth_cap(): void
    {
        $root = JudgingCriteria::factory()->create(['judging_round_id' => $this->round->id]);
        $child = JudgingCriteria::factory()->create(['judging_round_id' => $this->round->id, 'parent_id' => $root->id]);

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.criteria.store', $this->round), [
                'name' => 'Too Deep',
                'minimum_score' => 0,
                'maximum_score' => 10,
                'parent_id' => $child->id,
            ])
            ->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('judging_criteria', ['name' => 'Too Deep']);
    }

    public function test_parent_from_a_different_round_is_rejected(): void
    {
        $foreignRoot = JudgingCriteria::factory()->create();

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.criteria.store', $this->round), [
                'name' => 'Cross Round',
                'minimum_score' => 0,
                'maximum_score' => 10,
                'parent_id' => $foreignRoot->id,
            ])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_maximum_score_below_minimum_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.criteria.store', $this->round), [
                'name' => 'Bad Range',
                'minimum_score' => 50,
                'maximum_score' => 10,
            ])
            ->assertSessionHasErrors('maximum_score');
    }

    public function test_owner_can_delete_a_criterion(): void
    {
        $criterion = JudgingCriteria::factory()->create(['judging_round_id' => $this->round->id]);

        $this->actingAs($this->owner)->delete(route('dashboard.judging-criteria.destroy', $criterion));

        $this->assertDatabaseMissing('judging_criteria', ['id' => $criterion->id]);
    }

    /**
     * Guard added Sprint 9 (fr-scoring.md Keputusan H) — a criterion with
     * saved scores is competition history, not disposable config.
     */
    public function test_a_criterion_with_saved_scores_cannot_be_deleted(): void
    {
        $criterion = JudgingCriteria::factory()->create([
            'judging_round_id' => $this->round->id,
            'minimum_score' => 0,
            'maximum_score' => 100,
        ]);
        $event = $this->round->category->event;
        $judge = Judge::factory()->create(['organization_id' => $this->organization->id]);
        $entry = CompetitionEntry::factory()->create([
            'event_id' => $event->id,
            'event_category_id' => $this->round->category->id,
        ]);
        $sheet = ScoreSheet::factory()->create([
            'judging_round_id' => $this->round->id,
            'competition_entry_id' => $entry->id,
            'judge_id' => $judge->id,
        ]);
        Score::factory()->create(['score_sheet_id' => $sheet->id, 'judging_criteria_id' => $criterion->id, 'score' => 80]);

        $this->actingAs($this->owner)
            ->delete(route('dashboard.judging-criteria.destroy', $criterion))
            ->assertRedirect(route('dashboard.events.show', $event));

        $this->assertDatabaseHas('judging_criteria', ['id' => $criterion->id]);
    }

    public function test_member_role_cannot_manage_criteria(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);

        $this->actingAs($member)
            ->post(route('dashboard.judging-rounds.criteria.store', $this->round), [
                'name' => 'Nope',
                'minimum_score' => 0,
                'maximum_score' => 10,
            ])
            ->assertForbidden();
    }
}
