<?php

namespace Tests\Feature\CompetitionEntry;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Adversarial: "How can User A generate numbers, assign a number, or change
 * the status of an entry belonging to Event B?" Mirrors Event/TenantIsolationTest.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected Event $eventA;

    protected User $userB;

    protected Event $eventB;

    protected CompetitionEntry $entryB;

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
        $this->entryB = CompetitionEntry::factory()->create(['event_id' => $this->eventB->id]);
    }

    public function test_user_a_cannot_assign_a_number_to_event_b_entry(): void
    {
        $this->actingAs($this->userA)
            ->patch(route('dashboard.competition-entries.number', $this->entryB), ['competition_number' => 'A01'])
            ->assertForbidden();

        $this->assertNull($this->entryB->fresh()->competition_number);
    }

    public function test_user_a_cannot_change_status_of_event_b_entry(): void
    {
        $this->actingAs($this->userA)
            ->patch(route('dashboard.competition-entries.status', $this->entryB), ['status' => 'checked_in'])
            ->assertForbidden();
    }

    public function test_user_a_cannot_generate_numbers_for_event_b(): void
    {
        $this->actingAs($this->userA)
            ->post(route('dashboard.events.competition.numbers.generate', $this->eventB), [
                'event_category_id' => $this->entryB->event_category_id,
                'start' => 1,
                'digits' => 2,
            ])
            ->assertForbidden();
    }

    public function test_category_from_event_a_cannot_be_used_to_generate_numbers_on_event_b(): void
    {
        $categoryA = EventCategory::factory()->create(['event_id' => $this->eventA->id]);

        $this->actingAs($this->userB)
            ->post(route('dashboard.events.competition.numbers.generate', $this->eventB), [
                'event_category_id' => $categoryA->id,
                'start' => 1,
                'digits' => 2,
            ])
            ->assertSessionHasErrors('event_category_id');
    }

    public function test_guest_cannot_touch_any_competition_route(): void
    {
        $this->patch(route('dashboard.competition-entries.number', $this->entryB), ['competition_number' => 'A01'])
            ->assertRedirect(route('login'));
    }
}
