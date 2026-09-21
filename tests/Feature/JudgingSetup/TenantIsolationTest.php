<?php

namespace Tests\Feature\JudgingSetup;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Adversarial across Round + Criteria together — both derive tenant
 * ownership from the same event/organization chain, so one combined suite
 * (mirrors Event/TenantIsolationTest's shape).
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected Event $eventA;

    protected User $userB;

    protected Event $eventB;

    protected EventCategory $categoryB;

    protected JudgingRound $roundB;

    protected JudgingCriteria $criteriaB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create();
        $orgA = Organization::factory()->create();
        $orgA->users()->attach($this->userA, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->eventA = Event::factory()->create(['organization_id' => $orgA->id]);

        $this->userB = User::factory()->create();
        $orgB = Organization::factory()->create();
        $orgB->users()->attach($this->userB, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->eventB = Event::factory()->create(['organization_id' => $orgB->id]);
        $this->categoryB = EventCategory::factory()->create(['event_id' => $this->eventB->id]);
        $this->roundB = JudgingRound::factory()->create(['event_category_id' => $this->categoryB->id]);
        $this->criteriaB = JudgingCriteria::factory()->create(['judging_round_id' => $this->roundB->id]);
    }

    public function test_user_a_cannot_add_a_round_to_event_b(): void
    {
        $this->actingAs($this->userA)
            ->post(route('dashboard.events.categories.rounds.store', [$this->eventB, $this->categoryB]), ['name' => 'Intruder Round'])
            ->assertForbidden();
    }

    public function test_user_a_cannot_update_or_delete_round_b(): void
    {
        $this->actingAs($this->userA)
            ->put(route('dashboard.judging-rounds.update', $this->roundB), ['name' => 'Hijacked'])
            ->assertForbidden();

        $this->actingAs($this->userA)
            ->delete(route('dashboard.judging-rounds.destroy', $this->roundB))
            ->assertForbidden();

        $this->assertDatabaseHas('judging_rounds', ['id' => $this->roundB->id]);
    }

    public function test_user_a_cannot_add_criteria_to_round_b(): void
    {
        $this->actingAs($this->userA)
            ->post(route('dashboard.judging-rounds.criteria.store', $this->roundB), [
                'name' => 'Intruder Criteria',
                'minimum_score' => 0,
                'maximum_score' => 10,
            ])
            ->assertForbidden();
    }

    public function test_user_a_cannot_update_or_delete_criteria_b(): void
    {
        $this->actingAs($this->userA)
            ->put(route('dashboard.judging-criteria.update', $this->criteriaB), [
                'name' => 'Hijacked',
                'minimum_score' => 0,
                'maximum_score' => 10,
            ])
            ->assertForbidden();

        $this->actingAs($this->userA)
            ->delete(route('dashboard.judging-criteria.destroy', $this->criteriaB))
            ->assertForbidden();

        $this->assertDatabaseHas('judging_criteria', ['id' => $this->criteriaB->id]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('dashboard.events.categories.rounds.store', [$this->eventB, $this->categoryB]), [])
            ->assertRedirect(route('login'));
    }
}
