<?php

namespace Tests\Feature\JudgeAssignment;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Judge;
use App\Models\JudgeAssignment;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected User $userB;

    protected JudgingRound $roundB;

    protected Judge $judgeB;

    protected JudgeAssignment $assignmentB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create();
        $orgA = Organization::factory()->create();
        $orgA->users()->attach($this->userA, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $this->userB = User::factory()->create();
        $orgB = Organization::factory()->create();
        $orgB->users()->attach($this->userB, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $eventB = Event::factory()->create(['organization_id' => $orgB->id]);
        $categoryB = EventCategory::factory()->create(['event_id' => $eventB->id]);
        $this->roundB = JudgingRound::factory()->create(['event_category_id' => $categoryB->id]);
        $this->judgeB = Judge::factory()->create(['organization_id' => $orgB->id]);
        $this->assignmentB = JudgeAssignment::factory()->create([
            'judging_round_id' => $this->roundB->id,
            'judge_id' => $this->judgeB->id,
        ]);
    }

    public function test_user_a_cannot_assign_a_judge_in_round_b(): void
    {
        $this->actingAs($this->userA)
            ->post(route('dashboard.judging-rounds.assignments.store', $this->roundB), ['judge_id' => $this->judgeB->id])
            ->assertForbidden();
    }

    public function test_user_a_cannot_remove_assignment_b(): void
    {
        $this->actingAs($this->userA)
            ->delete(route('dashboard.judge-assignments.destroy', $this->assignmentB))
            ->assertForbidden();

        $this->assertDatabaseHas('judge_assignments', ['id' => $this->assignmentB->id]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('dashboard.judging-rounds.assignments.store', $this->roundB), [])
            ->assertRedirect(route('login'));
    }
}
