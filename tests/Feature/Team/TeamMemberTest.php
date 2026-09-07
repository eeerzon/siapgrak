<?php

namespace Tests\Feature\Team;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_roster_member(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $team = Team::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($owner)->post(route('dashboard.teams.members.store', $team), [
            'name' => 'Budi Santoso',
            'gender' => 'L',
            'member_role' => 'Danton',
        ]);

        $response->assertRedirect(route('dashboard.teams.members.index', $team));
        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'name' => 'Budi Santoso',
            'member_role' => 'Danton',
        ]);
    }

    public function test_member_role_cannot_add_roster_member(): void
    {
        $member = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);
        $team = Team::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($member)->post(route('dashboard.teams.members.store', $team), [
            'name' => 'Should Not Be Added',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('team_members', ['name' => 'Should Not Be Added']);
    }

    public function test_member_role_can_still_view_roster(): void
    {
        $member = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);
        $team = Team::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($member)
            ->get(route('dashboard.teams.members.index', $team))
            ->assertOk();
    }

    public function test_roster_name_is_required(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $team = Team::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($owner)->post(route('dashboard.teams.members.store', $team), [
            'gender' => 'L',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_owner_can_remove_roster_member(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $team = Team::factory()->create(['organization_id' => $organization->id]);
        $member = $team->members()->create(['name' => 'To Be Removed']);

        $response = $this->actingAs($owner)->delete(route('dashboard.teams.members.destroy', [$team, $member]));

        $response->assertRedirect(route('dashboard.teams.members.index', $team));
        $this->assertDatabaseMissing('team_members', ['id' => $member->id]);
    }
}
