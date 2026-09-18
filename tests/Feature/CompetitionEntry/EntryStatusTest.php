<?php

namespace Tests\Feature\CompetitionEntry;

use App\Enums\CompetitionEntryStatus;
use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntryStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
    }

    protected function entry(CompetitionEntryStatus $status = CompetitionEntryStatus::Registered): CompetitionEntry
    {
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);

        return CompetitionEntry::factory()->create(['event_id' => $event->id, 'status' => $status]);
    }

    public function test_registered_to_checked_in_stamps_checked_in_at_once(): void
    {
        $entry = $this->entry();

        $this->actingAs($this->owner)
            ->patch(route('dashboard.competition-entries.status', $entry), ['status' => 'checked_in'])
            ->assertRedirect(route('dashboard.events.show', $entry->event_id));

        $entry->refresh();
        $this->assertSame(CompetitionEntryStatus::CheckedIn, $entry->status);
        $this->assertNotNull($entry->checked_in_at);
    }

    public function test_full_happy_path_progresses_through_every_state(): void
    {
        $entry = $this->entry();

        foreach (['checked_in', 'ready', 'on_deck', 'performing', 'finished'] as $status) {
            $this->actingAs($this->owner)->patch(route('dashboard.competition-entries.status', $entry), ['status' => $status]);
        }

        $this->assertSame(CompetitionEntryStatus::Finished, $entry->fresh()->status);
    }

    public function test_skipping_a_state_is_rejected(): void
    {
        $entry = $this->entry();

        $this->actingAs($this->owner)
            ->patch(route('dashboard.competition-entries.status', $entry), ['status' => 'performing'])
            ->assertSessionHasErrors('status');

        $this->assertSame(CompetitionEntryStatus::Registered, $entry->fresh()->status);
    }

    public function test_terminal_states_reject_further_transitions(): void
    {
        foreach ([CompetitionEntryStatus::Finished, CompetitionEntryStatus::Withdrawn, CompetitionEntryStatus::Disqualified] as $terminal) {
            $entry = $this->entry($terminal);

            $this->actingAs($this->owner)
                ->patch(route('dashboard.competition-entries.status', $entry), ['status' => 'checked_in'])
                ->assertSessionHasErrors('status');

            $this->assertSame($terminal, $entry->fresh()->status);
        }
    }

    public function test_member_role_cannot_change_entry_status(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);
        $entry = $this->entry();

        $this->actingAs($member)
            ->patch(route('dashboard.competition-entries.status', $entry), ['status' => 'checked_in'])
            ->assertForbidden();
    }
}
