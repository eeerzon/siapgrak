<?php

namespace Tests\Feature\Team;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_team_in_own_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user, ['role' => OrganizationRole::Member, 'status' => Status::Active]);

        $response = $this->actingAs($user)->post(route('dashboard.teams.store'), [
            'organization_id' => $organization->id,
            'name' => 'Paskibra Utama',
        ]);

        $team = Team::where('name', 'Paskibra Utama')->firstOrFail();
        $response->assertRedirect(route('dashboard.teams.show', $team));
        $this->assertSame($organization->id, $team->organization_id);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $response = $this->actingAs($user)->post(route('dashboard.teams.store'), [
            'organization_id' => $organization->id,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_organization_id_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('dashboard.teams.store'), [
            'name' => 'Team Without Org',
        ]);

        $response->assertSessionHasErrors('organization_id');
        $this->assertDatabaseMissing('teams', ['name' => 'Team Without Org']);
    }

    public function test_user_cannot_attach_team_to_foreign_organization(): void
    {
        $user = User::factory()->create();
        $ownOrganization = Organization::factory()->create();
        $user->organizations()->attach($ownOrganization, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $foreignOrganization = Organization::factory()->create();

        $response = $this->actingAs($user)->post(route('dashboard.teams.store'), [
            'organization_id' => $foreignOrganization->id,
            'name' => 'Hijacked Team',
        ]);

        $response->assertSessionHasErrors('organization_id');
        $this->assertDatabaseMissing('teams', ['name' => 'Hijacked Team']);
    }

    public function test_invalid_organization_id_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('dashboard.teams.store'), [
            'organization_id' => 999999,
            'name' => 'Ghost Org Team',
        ]);

        $response->assertSessionHasErrors('organization_id');
        $this->assertDatabaseMissing('teams', ['name' => 'Ghost Org Team']);
    }

    public function test_slug_is_unique(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        Team::factory()->create(['slug' => 'dup-team-slug']);

        $response = $this->actingAs($user)->post(route('dashboard.teams.store'), [
            'organization_id' => $organization->id,
            'name' => 'New Team',
            'slug' => 'dup-team-slug',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_guest_cannot_create_team(): void
    {
        $response = $this->post(route('dashboard.teams.store'), [
            'name' => 'Guest Team',
        ]);

        $response->assertRedirect(route('login'));
    }
}
