<?php

namespace Tests\Feature\Organization;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Adversarial: "How can User A read or modify Organization B?"
 * See fr-organization-tenancy.md Testing Plan and CLAUDE.md Constitution.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected Organization $organizationA;

    protected User $userB;

    protected Organization $organizationB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create();
        $this->organizationA = Organization::factory()->create();
        $this->organizationA->users()->attach($this->userA, [
            'role' => OrganizationRole::Owner,
            'status' => Status::Active,
        ]);

        $this->userB = User::factory()->create();
        $this->organizationB = Organization::factory()->create();
        $this->organizationB->users()->attach($this->userB, [
            'role' => OrganizationRole::Owner,
            'status' => Status::Active,
        ]);
    }

    public function test_user_a_can_view_own_organization(): void
    {
        $response = $this->actingAs($this->userA)
            ->get(route('dashboard.organizations.show', $this->organizationA));

        $response->assertOk();
    }

    public function test_user_a_cannot_view_organization_b_via_direct_url(): void
    {
        $response = $this->actingAs($this->userA)
            ->get(route('dashboard.organizations.show', $this->organizationB));

        $response->assertForbidden();
    }

    public function test_user_a_cannot_open_edit_form_of_organization_b(): void
    {
        $response = $this->actingAs($this->userA)
            ->get(route('dashboard.organizations.edit', $this->organizationB));

        $response->assertForbidden();
    }

    public function test_user_a_cannot_update_organization_b_via_manipulated_payload(): void
    {
        $response = $this->actingAs($this->userA)->put(
            route('dashboard.organizations.update', $this->organizationB),
            [
                'name' => 'Hijacked Name',
                'slug' => $this->organizationB->slug,
                'type' => $this->organizationB->type->value,
            ],
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('organizations', ['name' => 'Hijacked Name']);
    }

    public function test_user_a_cannot_view_organization_b_members(): void
    {
        $response = $this->actingAs($this->userA)
            ->get(route('dashboard.organizations.members.index', $this->organizationB));

        $response->assertForbidden();
    }

    public function test_user_a_cannot_add_member_to_organization_b(): void
    {
        $intruder = User::factory()->create();

        $response = $this->actingAs($this->userA)->post(
            route('dashboard.organizations.members.store', $this->organizationB),
            ['email' => $intruder->email, 'role' => OrganizationRole::Member->value],
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('organization_users', [
            'organization_id' => $this->organizationB->id,
            'user_id' => $intruder->id_user,
        ]);
    }

    public function test_user_a_cannot_remove_a_member_from_organization_b(): void
    {
        $membership = $this->organizationB->memberships()->where('user_id', $this->userB->id_user)->firstOrFail();

        $response = $this->actingAs($this->userA)->delete(
            route('dashboard.organizations.members.destroy', [$this->organizationB, $membership]),
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('organization_users', ['id' => $membership->id]);
    }

    public function test_membership_id_from_organization_b_cannot_be_manipulated_via_organization_a_url(): void
    {
        // User A is owner of Organization A, and tries to reuse a membership ID that
        // actually belongs to Organization B by swapping the {organization} route
        // segment — route-model-binding alone would authorize this via `update` on A,
        // so the controller must independently verify the membership belongs to A.
        $membershipInB = $this->organizationB->memberships()->where('user_id', $this->userB->id_user)->firstOrFail();

        $response = $this->actingAs($this->userA)->patch(
            route('dashboard.organizations.members.update', [$this->organizationA, $membershipInB]),
            ['role' => OrganizationRole::Admin->value],
        );

        $response->assertNotFound();
        $this->assertDatabaseHas('organization_users', [
            'id' => $membershipInB->id,
            'role' => OrganizationRole::Owner->value,
        ]);
    }

    public function test_guest_is_redirected_to_login_for_any_organization_route(): void
    {
        $this->get(route('dashboard.organizations.show', $this->organizationA))
            ->assertRedirect(route('login'));

        $this->get(route('dashboard.organizations.index'))
            ->assertRedirect(route('login'));
    }
}
