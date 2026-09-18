<?php

namespace Tests\Feature\Registration;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use App\Models\Team;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_team_can_register_to_a_given_category_only_once(): void
    {
        $event = Event::factory()->create();
        $category = EventCategory::factory()->create(['event_id' => $event->id]);
        $team = Team::factory()->create();

        Registration::factory()->create([
            'event_id' => $event->id,
            'event_category_id' => $category->id,
            'team_id' => $team->id,
        ]);

        $this->expectException(QueryException::class);
        Registration::factory()->create([
            'event_id' => $event->id,
            'event_category_id' => $category->id,
            'team_id' => $team->id,
        ]);
    }

    public function test_event_category_and_team_cannot_be_hard_deleted_while_registered(): void
    {
        $registration = Registration::factory()->create();

        foreach ([$registration->event, $registration->category, $registration->team] as $parent) {
            try {
                $parent->delete();
                $this->fail('Expected a foreign key violation deleting '.$parent::class);
            } catch (QueryException $e) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_deleting_a_registration_cascades_to_members_and_documents(): void
    {
        $registration = Registration::factory()->create();
        $member = $registration->members()->create(['name' => 'Doomed']);
        $document = RegistrationDocument::factory()->create(['registration_id' => $registration->id]);

        $registration->delete();

        $this->assertDatabaseMissing('registration_members', ['id' => $member->id]);
        $this->assertDatabaseMissing('registration_documents', ['id' => $document->id]);
    }

    public function test_registration_gets_an_auto_assigned_public_id(): void
    {
        $this->assertStringStartsWith('reg_', Registration::factory()->create()->public_id);
    }

    public function test_registration_members_snapshot_survives_team_member_deletion(): void
    {
        $registration = Registration::factory()->create();
        $team = $registration->team;
        $teamMember = $team->members()->create(['name' => 'Snapshot Source']);

        $line = $registration->members()->create([
            'name' => 'Snapshot Source',
            'team_member_id' => $teamMember->id,
        ]);

        $teamMember->delete();

        $line->refresh();
        $this->assertSame('Snapshot Source', $line->name);
        $this->assertNull($line->team_member_id);
    }
}
