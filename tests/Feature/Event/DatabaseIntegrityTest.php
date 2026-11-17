<?php

namespace Tests\Feature\Event;

use App\Enums\EventStaffRole;
use App\Models\Event;
use App\Models\EventStaff;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_cannot_be_hard_deleted_while_it_owns_events(): void
    {
        $event = Event::factory()->create();

        $this->expectException(QueryException::class);
        $event->organization->delete();
    }

    public function test_deleting_an_event_cascades_to_its_categories_and_staff(): void
    {
        $event = Event::factory()->create();
        $category = $event->categories()->create(['name' => 'Doomed Category']);
        $staffUser = User::factory()->create();
        $staff = $event->staff()->create(['user_id' => $staffUser->id_user, 'role' => EventStaffRole::Viewer]);

        $event->delete();

        $this->assertDatabaseMissing('event_categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('event_staff', ['id' => $staff->id]);
    }

    public function test_slug_is_unique_at_database_level(): void
    {
        Event::factory()->create(['slug' => 'one-and-only']);

        $this->expectException(QueryException::class);
        Event::factory()->create(['slug' => 'one-and-only']);
    }

    public function test_category_code_is_unique_per_event(): void
    {
        $event = Event::factory()->create();
        $event->categories()->create(['name' => 'A', 'code' => 'X']);

        $this->expectException(QueryException::class);
        $event->categories()->create(['name' => 'B', 'code' => 'X']);
    }

    public function test_multiple_categories_without_a_code_are_allowed_in_one_event(): void
    {
        $event = Event::factory()->create();
        $event->categories()->create(['name' => 'No Code 1']);
        $event->categories()->create(['name' => 'No Code 2']);

        $this->assertSame(2, $event->categories()->count());
    }

    public function test_a_user_can_only_be_staff_once_per_event(): void
    {
        $event = Event::factory()->create();
        $user = User::factory()->create();
        EventStaff::factory()->create(['event_id' => $event->id, 'user_id' => $user->id_user]);

        $this->expectException(QueryException::class);
        EventStaff::factory()->create(['event_id' => $event->id, 'user_id' => $user->id_user]);
    }

    public function test_event_gets_an_auto_assigned_public_id(): void
    {
        $event = Event::factory()->create();

        $this->assertStringStartsWith('evt_', $event->public_id);
    }
}
