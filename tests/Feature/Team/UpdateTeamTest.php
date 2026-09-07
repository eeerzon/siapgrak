<?php

namespace Tests\Feature\Team;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_team(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $team = Team::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($owner)->put(route('dashboard.teams.update', $team), [
            'name' => 'Updated Team Name',
            'slug' => $team->slug,
        ]);

        $response->assertRedirect(route('dashboard.teams.show', $team));
        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'Updated Team Name']);
    }

    public function test_member_role_cannot_update_team(): void
    {
        $member = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);
        $team = Team::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($member)->put(route('dashboard.teams.update', $team), [
            'name' => 'Should Not Change',
            'slug' => $team->slug,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('teams', ['name' => 'Should Not Change']);
    }

    public function test_organization_id_cannot_be_changed_via_update(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $team = Team::factory()->create(['organization_id' => $organization->id]);

        $otherOrganization = Organization::factory()->create();

        $this->actingAs($owner)->put(route('dashboard.teams.update', $team), [
            'name' => $team->name,
            'slug' => $team->slug,
            'organization_id' => $otherOrganization->id, // not a validated field — must be ignored
        ]);

        $this->assertSame($organization->id, $team->fresh()->organization_id);
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $teamA = Team::factory()->create(['organization_id' => $organization->id, 'slug' => 'taken-team-slug']);
        $teamB = Team::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($owner)->put(route('dashboard.teams.update', $teamB), [
            'name' => $teamB->name,
            'slug' => 'taken-team-slug',
        ]);

        $response->assertSessionHasErrors('slug');
    }
}
