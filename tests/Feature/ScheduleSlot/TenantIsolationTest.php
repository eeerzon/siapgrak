<?php

namespace Tests\Feature\ScheduleSlot;

use App\Enums\OrganizationRole;
use App\Enums\ScheduleSlotType;
use App\Enums\Status;
use App\Models\Event;
use App\Models\EventScheduleSlot;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected Event $eventA;

    protected User $userB;

    protected Event $eventB;

    protected EventScheduleSlot $slotB;

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
        $this->slotB = EventScheduleSlot::factory()->create(['event_id' => $this->eventB->id]);
    }

    public function test_user_a_cannot_add_a_slot_to_event_b(): void
    {
        $this->actingAs($this->userA)
            ->post(route('dashboard.events.schedule.store', $this->eventB), [
                'start_at' => '2026-10-12 08:00:00',
                'slot_type' => ScheduleSlotType::Break->value,
            ])
            ->assertForbidden();
    }

    public function test_user_a_cannot_edit_or_delete_event_b_slot(): void
    {
        $this->actingAs($this->userA)
            ->put(route('dashboard.events.schedule.update', [$this->eventB, $this->slotB]), [
                'start_at' => '2026-10-12 08:00:00',
                'slot_type' => ScheduleSlotType::Break->value,
            ])
            ->assertForbidden();

        $this->actingAs($this->userA)
            ->delete(route('dashboard.events.schedule.destroy', [$this->eventB, $this->slotB]))
            ->assertForbidden();

        $this->assertDatabaseHas('event_schedule_slots', ['id' => $this->slotB->id]);
    }

    public function test_slot_of_event_b_not_reachable_via_event_a_url(): void
    {
        $this->actingAs($this->userA)
            ->delete(route('dashboard.events.schedule.destroy', [$this->eventA, $this->slotB]))
            ->assertNotFound();

        $this->assertDatabaseHas('event_schedule_slots', ['id' => $this->slotB->id]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('dashboard.events.schedule.store', $this->eventA), [])
            ->assertRedirect(route('login'));
    }
}
