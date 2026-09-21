<?php

namespace Tests\Feature\ScheduleSlot;

use App\Enums\OrganizationRole;
use App\Enums\ScheduleSlotType;
use App\Enums\Status;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventScheduleSlot;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->event = Event::factory()->create(['organization_id' => $this->organization->id]);
    }

    public function test_owner_can_add_a_slot_appended_to_the_end(): void
    {
        // Explicit title: the factory default randomly picks from a small
        // set that includes "Opening Ceremony" (the exact title this test
        // posts below) — an unlucky roll made the where('title', ...)
        // lookup below match this pre-existing row instead of the new one,
        // an intermittent flake unrelated to the append logic under test.
        EventScheduleSlot::factory()->create(['event_id' => $this->event->id, 'display_order' => 5, 'title' => 'Existing Slot']);

        $this->actingAs($this->owner)
            ->post(route('dashboard.events.schedule.store', $this->event), [
                'title' => 'Opening Ceremony',
                'start_at' => '2026-10-12 08:00:00',
                'slot_type' => ScheduleSlotType::Ceremony->value,
            ])
            ->assertRedirect(route('dashboard.events.show', $this->event));

        $slot = EventScheduleSlot::where('title', 'Opening Ceremony')->firstOrFail();
        $this->assertSame(6, $slot->display_order);
    }

    public function test_category_from_another_event_is_rejected(): void
    {
        $foreignCategory = EventCategory::factory()->create();

        $this->actingAs($this->owner)
            ->post(route('dashboard.events.schedule.store', $this->event), [
                'start_at' => '2026-10-12 08:00:00',
                'slot_type' => ScheduleSlotType::Competition->value,
                'event_category_id' => $foreignCategory->id,
            ])
            ->assertSessionHasErrors('event_category_id');
    }

    public function test_end_at_before_start_at_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->post(route('dashboard.events.schedule.store', $this->event), [
                'start_at' => '2026-10-12 08:00:00',
                'end_at' => '2026-10-12 07:00:00',
                'slot_type' => ScheduleSlotType::Break->value,
            ])
            ->assertSessionHasErrors('end_at');
    }

    public function test_owner_can_edit_every_field_including_category(): void
    {
        $slot = EventScheduleSlot::factory()->create(['event_id' => $this->event->id, 'title' => 'Old']);
        $category = EventCategory::factory()->create(['event_id' => $this->event->id]);

        $this->actingAs($this->owner)->put(route('dashboard.events.schedule.update', [$this->event, $slot]), [
            'title' => 'New Title',
            'start_at' => '2026-10-12 09:00:00',
            'slot_type' => ScheduleSlotType::Competition->value,
            'event_category_id' => $category->id,
        ]);

        $slot->refresh();
        $this->assertSame('New Title', $slot->title);
        $this->assertSame($category->id, $slot->event_category_id);
    }

    public function test_move_up_and_down_swap_adjacent_order(): void
    {
        $first = EventScheduleSlot::factory()->create(['event_id' => $this->event->id, 'display_order' => 0]);
        $second = EventScheduleSlot::factory()->create(['event_id' => $this->event->id, 'display_order' => 1]);

        $this->actingAs($this->owner)->post(route('dashboard.events.schedule.move-up', [$this->event, $second]));

        $this->assertSame(0, $second->fresh()->display_order);
        $this->assertSame(1, $first->fresh()->display_order);

        $this->actingAs($this->owner)->post(route('dashboard.events.schedule.move-down', [$this->event, $second]));

        $this->assertSame(1, $second->fresh()->display_order);
        $this->assertSame(0, $first->fresh()->display_order);
    }

    /**
     * Regression: with only 2 slots, "the previous/next row" and "the
     * smallest/largest matching row" are the same row, so a bug that mixes
     * those two up can't be caught. Needs >= 3 slots to actually prove
     * moveUp/moveDown swap with the true NEIGHBOUR, not an arbitrary one —
     * this is exactly the bug found manually 2026-09-14 (fr-schedule.md).
     */
    public function test_move_up_with_three_slots_swaps_only_the_immediate_neighbour(): void
    {
        $first = EventScheduleSlot::factory()->create(['event_id' => $this->event->id, 'display_order' => 0]);
        $second = EventScheduleSlot::factory()->create(['event_id' => $this->event->id, 'display_order' => 1]);
        $third = EventScheduleSlot::factory()->create(['event_id' => $this->event->id, 'display_order' => 2]);

        $this->actingAs($this->owner)->post(route('dashboard.events.schedule.move-up', [$this->event, $third]));

        $this->assertSame(0, $first->fresh()->display_order, 'the non-adjacent slot must not move');
        $this->assertSame(2, $second->fresh()->display_order, 'third should swap with its immediate neighbour (second)');
        $this->assertSame(1, $third->fresh()->display_order);
    }

    public function test_moving_the_first_slot_up_is_a_no_op(): void
    {
        $first = EventScheduleSlot::factory()->create(['event_id' => $this->event->id, 'display_order' => 0]);

        $this->actingAs($this->owner)->post(route('dashboard.events.schedule.move-up', [$this->event, $first]));

        $this->assertSame(0, $first->fresh()->display_order);
    }

    public function test_owner_can_hard_delete_a_slot(): void
    {
        $slot = EventScheduleSlot::factory()->create(['event_id' => $this->event->id]);

        $this->actingAs($this->owner)->delete(route('dashboard.events.schedule.destroy', [$this->event, $slot]));

        $this->assertDatabaseMissing('event_schedule_slots', ['id' => $slot->id]);
    }

    public function test_member_role_can_view_but_not_mutate(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);
        $slot = EventScheduleSlot::factory()->create(['event_id' => $this->event->id]);

        $this->actingAs($member)->get(route('dashboard.events.show', $this->event))->assertOk();

        $this->actingAs($member)
            ->post(route('dashboard.events.schedule.store', $this->event), [
                'start_at' => '2026-10-12 08:00:00',
                'slot_type' => ScheduleSlotType::Break->value,
            ])
            ->assertForbidden();

        $this->actingAs($member)
            ->delete(route('dashboard.events.schedule.destroy', [$this->event, $slot]))
            ->assertForbidden();
    }
}
