<?php

namespace Tests\Feature\CompetitionEntry;

use App\Models\CompetitionEntry;
use App\Models\Registration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_registration_can_have_at_most_one_entry(): void
    {
        $registration = Registration::factory()->create();
        CompetitionEntry::factory()->create([
            'event_id' => $registration->event_id,
            'event_category_id' => $registration->event_category_id,
            'team_id' => $registration->team_id,
            'registration_id' => $registration->id,
        ]);

        $this->expectException(QueryException::class);
        CompetitionEntry::factory()->create([
            'event_id' => $registration->event_id,
            'event_category_id' => $registration->event_category_id,
            'team_id' => $registration->team_id,
            'registration_id' => $registration->id,
        ]);
    }

    public function test_competition_number_is_unique_per_category(): void
    {
        // competition_number isn't mass-assignable — assign directly, same as
        // CompetitionNumberService::assignManual() does.
        $entry = CompetitionEntry::factory()->create();
        $entry->competition_number = 'A01';
        $entry->save();

        $sameCategory = CompetitionEntry::factory()->create(['event_category_id' => $entry->event_category_id]);

        $this->expectException(QueryException::class);
        $sameCategory->competition_number = 'A01';
        $sameCategory->save();
    }

    public function test_parents_cannot_be_hard_deleted_while_an_entry_exists(): void
    {
        $entry = CompetitionEntry::factory()->create();

        foreach ([$entry->event, $entry->category, $entry->team, $entry->registration] as $parent) {
            try {
                $parent->delete();
                $this->fail('Expected a foreign key violation deleting '.$parent::class);
            } catch (QueryException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_entry_gets_an_auto_assigned_public_id(): void
    {
        $this->assertStringStartsWith('entry_', CompetitionEntry::factory()->create()->public_id);
    }
}
