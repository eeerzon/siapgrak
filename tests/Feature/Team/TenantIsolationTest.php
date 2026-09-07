<?php

namespace Tests\Feature\Team;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Adversarial: "How can User A see, edit, link, or move Team B?"
 * See fr-team-directory.md and CLAUDE.md Constitution.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected Organization $organizationA;

    protected Team $teamA;

    protected User $userB;

    protected Organization $organizationB;

    protected Team $teamB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create();
        $this->organizationA = Organization::factory()->create();
        $this->organizationA->users()->attach($this->userA, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->teamA = Team::factory()->create(['organization_id' => $this->organizationA->id]);

        $this->userB = User::factory()->create();
        $this->organizationB = Organization::factory()->create();
        $this->organizationB->users()->attach($this->userB, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->teamB = Team::factory()->create(['organization_id' => $this->organizationB->id]);
    }

    public function test_user_a_can_view_own_team(): void
    {
        $this->actingAs($this->userA)
            ->get(route('dashboard.teams.show', $this->teamA))
            ->assertOk();
    }

    public function test_user_a_cannot_view_team_b_via_direct_url(): void
    {
        $this->actingAs($this->userA)
            ->get(route('dashboard.teams.show', $this->teamB))
            ->assertForbidden();
    }

    public function test_user_a_cannot_open_edit_form_of_team_b(): void
    {
        $this->actingAs($this->userA)
            ->get(route('dashboard.teams.edit', $this->teamB))
            ->assertForbidden();
    }

    public function test_user_a_cannot_update_team_b_via_manipulated_payload(): void
    {
        $response = $this->actingAs($this->userA)->put(route('dashboard.teams.update', $this->teamB), [
            'name' => 'Hijacked Team B',
            'slug' => $this->teamB->slug,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('teams', ['name' => 'Hijacked Team B']);
    }

    public function test_user_a_cannot_view_team_b_roster(): void
    {
        $this->actingAs($this->userA)
            ->get(route('dashboard.teams.members.index', $this->teamB))
            ->assertForbidden();
    }

    public function test_user_a_cannot_add_member_to_team_b_roster(): void
    {
        $response = $this->actingAs($this->userA)->post(route('dashboard.teams.members.store', $this->teamB), [
            'name' => 'Intruder Member',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('team_members', ['team_id' => $this->teamB->id, 'name' => 'Intruder Member']);
    }

    public function test_user_a_cannot_remove_a_member_from_team_b_roster(): void
    {
        $member = $this->teamB->members()->create(['name' => 'Legit Member']);

        $response = $this->actingAs($this->userA)->delete(route('dashboard.teams.members.destroy', [$this->teamB, $member]));

        $response->assertForbidden();
        $this->assertDatabaseHas('team_members', ['id' => $member->id]);
    }

    public function test_member_id_from_team_b_cannot_be_manipulated_via_team_a_url(): void
    {
        // Owner of Team A tries to reuse a member ID that actually belongs to
        // Team B by swapping the {team} route segment — TeamPolicy alone
        // would authorize this via manageMembers on Team A, so the controller
        // must independently verify the member belongs to Team A.
        $memberInB = $this->teamB->members()->create(['name' => 'Roster B Member']);

        $response = $this->actingAs($this->userA)->delete(route('dashboard.teams.members.destroy', [$this->teamA, $memberInB]));

        $response->assertNotFound();
        $this->assertDatabaseHas('team_members', ['id' => $memberInB->id]);
    }

    public function test_user_a_cannot_create_team_under_organization_b(): void
    {
        $response = $this->actingAs($this->userA)->post(route('dashboard.teams.store'), [
            'organization_id' => $this->organizationB->id,
            'name' => 'Planted Team',
        ]);

        $response->assertSessionHasErrors('organization_id');
        $this->assertDatabaseMissing('teams', ['name' => 'Planted Team']);
    }

    public function test_team_index_only_shows_teams_from_own_organizations(): void
    {
        $response = $this->actingAs($this->userA)->get(route('dashboard.teams.index'));

        $response->assertOk();
        $response->assertSee($this->teamA->name);
        $response->assertDontSee($this->teamB->name);
    }

    public function test_guest_is_redirected_to_login_for_any_team_route(): void
    {
        $this->get(route('dashboard.teams.show', $this->teamA))
            ->assertRedirect(route('login'));

        $this->get(route('dashboard.teams.index'))
            ->assertRedirect(route('login'));
    }
}
