<?php

namespace Tests\Feature\JudgeAssignment;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Judge;
use App\Models\JudgeAssignment;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected JudgingRound $round;

    protected Judge $judge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $category = EventCategory::factory()->create(['event_id' => $event->id]);
        $this->round = JudgingRound::factory()->create(['event_category_id' => $category->id]);
        $this->judge = Judge::factory()->create(['organization_id' => $this->organization->id]);
    }

    public function test_owner_can_assign_a_judge_to_the_whole_round(): void
    {
        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.assignments.store', $this->round), ['judge_id' => $this->judge->id])
            ->assertRedirect(route('dashboard.events.show', $this->round->category->event));

        $this->assertDatabaseHas('judge_assignments', [
            'judging_round_id' => $this->round->id,
            'judge_id' => $this->judge->id,
            'judging_criteria_id' => null,
        ]);
    }

    public function test_owner_can_assign_a_judge_to_a_specific_criterion(): void
    {
        $criterion = JudgingCriteria::factory()->create(['judging_round_id' => $this->round->id]);

        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.assignments.store', $this->round), [
            'judge_id' => $this->judge->id,
            'judging_criteria_id' => $criterion->id,
        ]);

        $this->assertDatabaseHas('judge_assignments', [
            'judging_round_id' => $this->round->id,
            'judge_id' => $this->judge->id,
            'judging_criteria_id' => $criterion->id,
        ]);
    }

    public function test_a_second_whole_round_assignment_for_the_same_judge_is_rejected(): void
    {
        JudgeAssignment::factory()->create(['judging_round_id' => $this->round->id, 'judge_id' => $this->judge->id]);

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.assignments.store', $this->round), ['judge_id' => $this->judge->id])
            ->assertSessionHasErrors('judge_id');

        $this->assertSame(1, JudgeAssignment::where('judge_id', $this->judge->id)->count());
    }

    public function test_specific_criteria_assignment_is_rejected_once_judge_has_whole_round(): void
    {
        JudgeAssignment::factory()->create(['judging_round_id' => $this->round->id, 'judge_id' => $this->judge->id]);
        $criterion = JudgingCriteria::factory()->create(['judging_round_id' => $this->round->id]);

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.assignments.store', $this->round), [
                'judge_id' => $this->judge->id,
                'judging_criteria_id' => $criterion->id,
            ])
            ->assertSessionHasErrors('judge_id');
    }

    public function test_whole_round_assignment_is_rejected_once_judge_has_a_specific_criterion(): void
    {
        $criterion = JudgingCriteria::factory()->create(['judging_round_id' => $this->round->id]);
        JudgeAssignment::factory()->create([
            'judging_round_id' => $this->round->id,
            'judge_id' => $this->judge->id,
            'judging_criteria_id' => $criterion->id,
        ]);

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.assignments.store', $this->round), ['judge_id' => $this->judge->id])
            ->assertSessionHasErrors('judge_id');
    }

    public function test_judge_can_be_assigned_to_two_different_criteria_in_the_same_round(): void
    {
        $first = JudgingCriteria::factory()->create(['judging_round_id' => $this->round->id]);
        $second = JudgingCriteria::factory()->create(['judging_round_id' => $this->round->id]);

        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.assignments.store', $this->round), [
            'judge_id' => $this->judge->id,
            'judging_criteria_id' => $first->id,
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.assignments.store', $this->round), [
            'judge_id' => $this->judge->id,
            'judging_criteria_id' => $second->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, JudgeAssignment::where('judge_id', $this->judge->id)->count());
    }

    public function test_judge_from_a_foreign_organization_is_rejected(): void
    {
        $foreignJudge = Judge::factory()->create();

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.assignments.store', $this->round), ['judge_id' => $foreignJudge->id])
            ->assertSessionHasErrors('judge_id');
    }

    public function test_criterion_from_a_different_round_is_rejected(): void
    {
        $foreignCriterion = JudgingCriteria::factory()->create();

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.assignments.store', $this->round), [
                'judge_id' => $this->judge->id,
                'judging_criteria_id' => $foreignCriterion->id,
            ])
            ->assertSessionHasErrors('judging_criteria_id');
    }

    public function test_owner_can_remove_an_assignment(): void
    {
        $assignment = JudgeAssignment::factory()->create(['judging_round_id' => $this->round->id, 'judge_id' => $this->judge->id]);

        $this->actingAs($this->owner)->delete(route('dashboard.judge-assignments.destroy', $assignment));

        $this->assertDatabaseMissing('judge_assignments', ['id' => $assignment->id]);
    }

    public function test_deleting_a_criterion_cascades_to_its_assignment(): void
    {
        $criterion = JudgingCriteria::factory()->create(['judging_round_id' => $this->round->id]);
        $assignment = JudgeAssignment::factory()->create([
            'judging_round_id' => $this->round->id,
            'judge_id' => $this->judge->id,
            'judging_criteria_id' => $criterion->id,
        ]);

        $criterion->delete();

        $this->assertDatabaseMissing('judge_assignments', ['id' => $assignment->id]);
    }

    public function test_member_role_cannot_manage_assignments(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);

        $this->actingAs($member)
            ->post(route('dashboard.judging-rounds.assignments.store', $this->round), ['judge_id' => $this->judge->id])
            ->assertForbidden();
    }
}
