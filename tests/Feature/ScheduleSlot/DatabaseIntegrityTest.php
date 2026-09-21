<?php

namespace Tests\Feature\ScheduleSlot;

use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventScheduleSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_the_linked_category_detaches_the_slot_instead_of_removing_it(): void
    {
        $category = EventCategory::factory()->create();
        $slot = EventScheduleSlot::factory()->create(['event_id' => $category->event_id, 'event_category_id' => $category->id]);

        $category->delete();

        $this->assertDatabaseHas('event_schedule_slots', ['id' => $slot->id, 'event_category_id' => null]);
    }

    public function test_deleting_the_linked_entry_detaches_the_slot_instead_of_removing_it(): void
    {
        $entry = CompetitionEntry::factory()->create();
        $slot = EventScheduleSlot::factory()->create(['event_id' => $entry->event_id, 'competition_entry_id' => $entry->id]);

        // entries have no delete route in the app, but the FK behavior itself
        // is what's under test here.
        $entry->delete();

        $this->assertDatabaseHas('event_schedule_slots', ['id' => $slot->id, 'competition_entry_id' => null]);
    }

    public function test_deleting_the_event_cascades_to_its_slots(): void
    {
        $event = Event::factory()->create();
        $slot = EventScheduleSlot::factory()->create(['event_id' => $event->id]);

        $event->delete();

        $this->assertDatabaseMissing('event_schedule_slots', ['id' => $slot->id]);
    }
}
