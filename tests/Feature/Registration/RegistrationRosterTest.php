<?php

namespace Tests\Feature\Registration;

use App\Enums\OrganizationRole;
use App\Enums\RegistrationStatus;
use App\Enums\Status;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationRosterTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->team = Team::factory()->create(['organization_id' => $this->organization->id]);
    }

    protected function registration(RegistrationStatus $status = RegistrationStatus::Draft): Registration
    {
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);

        return Registration::factory()->create([
            'event_id' => $event->id,
            'team_id' => $this->team->id,
            'status' => $status,
        ]);
    }

    public function test_owner_can_add_roster_member_while_draft(): void
    {
        $registration = $this->registration();

        $this->actingAs($this->owner)
            ->post(route('dashboard.registrations.members.store', $registration), [
                'name' => 'Budi Santoso',
                'member_role' => 'Danton',
                'gender' => 'L',
            ])
            ->assertRedirect(route('dashboard.events.registrations.show', [$registration->event_id, $registration]));

        $this->assertDatabaseHas('registration_members', [
            'registration_id' => $registration->id,
            'name' => 'Budi Santoso',
            'member_role' => 'Danton',
        ]);
    }

    public function test_roster_line_is_a_snapshot_independent_of_team_members(): void
    {
        $registration = $this->registration();
        $teamMember = TeamMember::factory()->create(['team_id' => $this->team->id, 'name' => 'Original Name']);

        $this->actingAs($this->owner)->post(route('dashboard.registrations.members.store', $registration), [
            'name' => 'Original Name',
            'team_member_id' => $teamMember->id,
        ]);

        // rename then delete the source team member
        $teamMember->update(['name' => 'Renamed Later']);
        $teamMember->delete();

        $line = $registration->members()->firstOrFail();
        $this->assertSame('Original Name', $line->name);
        $this->assertNull($line->team_member_id); // SET NULL, snapshot survives
    }

    public function test_cannot_add_roster_member_once_submitted(): void
    {
        $registration = $this->registration(RegistrationStatus::Submitted);

        $this->actingAs($this->owner)
            ->post(route('dashboard.registrations.members.store', $registration), ['name' => 'Too Late'])
            ->assertForbidden();

        $this->assertDatabaseMissing('registration_members', ['name' => 'Too Late']);
    }

    public function test_owner_can_remove_roster_member_while_editable(): void
    {
        $registration = $this->registration(RegistrationStatus::RevisionRequested);
        $member = $registration->members()->create(['name' => 'To Remove']);

        $this->actingAs($this->owner)->delete(route('dashboard.registrations.members.destroy', [$registration, $member]));

        $this->assertDatabaseMissing('registration_members', ['id' => $member->id]);
    }

    public function test_team_member_id_from_another_team_is_rejected(): void
    {
        $registration = $this->registration();
        $foreignMember = TeamMember::factory()->create();

        $this->actingAs($this->owner)
            ->post(route('dashboard.registrations.members.store', $registration), [
                'name' => 'Someone',
                'team_member_id' => $foreignMember->id,
            ])
            ->assertSessionHasErrors('team_member_id');
    }

    public function test_member_role_cannot_manage_roster(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);
        $registration = $this->registration();

        $this->actingAs($member)
            ->post(route('dashboard.registrations.members.store', $registration), ['name' => 'Nope'])
            ->assertForbidden();
    }
}
