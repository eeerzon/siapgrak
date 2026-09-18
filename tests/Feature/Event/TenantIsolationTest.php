<?php

namespace Tests\Feature\Event;

use App\Enums\EventStaffRole;
use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Adversarial: "How can User A see, edit, cancel, or add categories/staff to
 * Event B?" Mirrors Team/TenantIsolationTest. See fr-event-category.md and
 * CLAUDE.md Constitution.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected Event $eventA;

    protected User $userB;

    protected Event $eventB;

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
    }

    public function test_user_a_can_view_own_event(): void
    {
        $this->actingAs($this->userA)->get(route('dashboard.events.show', $this->eventA))->assertOk();
    }

    public function test_user_a_cannot_view_event_b(): void
    {
        $this->actingAs($this->userA)->get(route('dashboard.events.show', $this->eventB))->assertForbidden();
    }

    public function test_user_a_cannot_open_edit_form_of_event_b(): void
    {
        $this->actingAs($this->userA)->get(route('dashboard.events.edit', $this->eventB))->assertForbidden();
    }

    public function test_user_a_cannot_update_event_b_via_manipulated_payload(): void
    {
        $this->actingAs($this->userA)->put(route('dashboard.events.update', $this->eventB), [
            'name' => 'Hijacked Event B',
            'slug' => $this->eventB->slug,
            'start_date' => $this->eventB->start_date->toDateString(),
            'end_date' => $this->eventB->end_date->toDateString(),
        ])->assertForbidden();

        $this->assertDatabaseMissing('events', ['name' => 'Hijacked Event B']);
    }

    public function test_user_a_cannot_change_status_of_event_b(): void
    {
        $this->actingAs($this->userA)
            ->patch(route('dashboard.events.status', $this->eventB), ['status' => 'cancelled'])
            ->assertForbidden();

        $this->assertSame($this->eventB->status, $this->eventB->fresh()->status);
    }

    public function test_user_a_cannot_add_category_to_event_b(): void
    {
        $this->actingAs($this->userA)
            ->post(route('dashboard.events.categories.store', $this->eventB), ['name' => 'Intruder Category'])
            ->assertForbidden();

        $this->assertDatabaseMissing('event_categories', ['name' => 'Intruder Category']);
    }

    public function test_user_a_cannot_add_staff_to_event_b(): void
    {
        $this->actingAs($this->userA)
            ->post(route('dashboard.events.staff.store', $this->eventB), [
                'user_id' => $this->userA->id,
                'role' => EventStaffRole::EventAdmin->value,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('event_staff', ['event_id' => $this->eventB->id, 'user_id' => $this->userA->id]);
    }

    public function test_event_index_only_shows_events_from_own_organizations(): void
    {
        $response = $this->actingAs($this->userA)->get(route('dashboard.events.index'));

        $response->assertOk();
        $response->assertSee($this->eventA->name);
        $response->assertDontSee($this->eventB->name);
    }

    public function test_guest_is_redirected_to_login_for_any_event_route(): void
    {
        $this->get(route('dashboard.events.index'))->assertRedirect(route('login'));
        $this->get(route('dashboard.events.show', $this->eventA))->assertRedirect(route('login'));
        $this->get(route('dashboard.events.create'))->assertRedirect(route('login'));
    }
}
