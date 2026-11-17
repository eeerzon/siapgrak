<?php

namespace Tests\Feature\Organization;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_a_member(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $newMember = User::factory()->create();

        $response = $this->actingAs($owner)->post(
            route('dashboard.organizations.members.store', $organization),
            ['email' => $newMember->email, 'role' => OrganizationRole::Member->value],
        );

        $response->assertRedirect(route('dashboard.organizations.members.index', $organization));
        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $organization->id,
            'user_id' => $newMember->id_user,
            'role' => OrganizationRole::Member->value,
        ]);
    }

    public function test_member_role_cannot_add_a_member(): void
    {
        $organization = Organization::factory()->create();

        $member = User::factory()->create();
        $organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);

        $newMember = User::factory()->create();

        $response = $this->actingAs($member)->post(
            route('dashboard.organizations.members.store', $organization),
            ['email' => $newMember->email, 'role' => OrganizationRole::Member->value],
        );

        $response->assertForbidden();
    }

    public function test_duplicate_membership_is_rejected(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $existingMember = User::factory()->create();
        $organization->users()->attach($existingMember, ['role' => OrganizationRole::Member, 'status' => Status::Active]);

        $response = $this->actingAs($owner)->post(
            route('dashboard.organizations.members.store', $organization),
            ['email' => $existingMember->email, 'role' => OrganizationRole::Admin->value],
        );

        $response->assertSessionHasErrors('email');
        $this->assertSame(
            1,
            $organization->memberships()->where('user_id', $existingMember->id_user)->count(),
        );
    }

    public function test_invalid_role_is_rejected(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $newMember = User::factory()->create();

        $response = $this->actingAs($owner)->post(
            route('dashboard.organizations.members.store', $organization),
            ['email' => $newMember->email, 'role' => 'super-admin-hacker'],
        );

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('organization_users', [
            'organization_id' => $organization->id,
            'user_id' => $newMember->id_user,
        ]);
    }

    public function test_adding_member_with_unknown_email_is_rejected(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $response = $this->actingAs($owner)->post(
            route('dashboard.organizations.members.store', $organization),
            ['email' => 'nobody@example.com', 'role' => OrganizationRole::Member->value],
        );

        $response->assertSessionHasErrors('email');
    }

    public function test_sole_owner_cannot_be_removed(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $membership = $organization->memberships()->where('user_id', $owner->id_user)->firstOrFail();

        $response = $this->actingAs($owner)->delete(
            route('dashboard.organizations.members.destroy', [$organization, $membership]),
        );

        $response->assertSessionHasErrors('member');
        $this->assertDatabaseHas('organization_users', ['id' => $membership->id]);
    }

    public function test_sole_owner_cannot_be_demoted(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $membership = $organization->memberships()->where('user_id', $owner->id_user)->firstOrFail();

        $response = $this->actingAs($owner)->patch(
            route('dashboard.organizations.members.update', [$organization, $membership]),
            ['role' => OrganizationRole::Admin->value],
        );

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseHas('organization_users', [
            'id' => $membership->id,
            'role' => OrganizationRole::Owner->value,
        ]);
    }

    public function test_owner_can_be_removed_when_another_owner_exists(): void
    {
        $ownerOne = User::factory()->create();
        $ownerTwo = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($ownerOne, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $organization->users()->attach($ownerTwo, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $membershipOne = $organization->memberships()->where('user_id', $ownerOne->id_user)->firstOrFail();

        $response = $this->actingAs($ownerTwo)->delete(
            route('dashboard.organizations.members.destroy', [$organization, $membershipOne]),
        );

        $response->assertRedirect(route('dashboard.organizations.members.index', $organization));
        $this->assertDatabaseMissing('organization_users', ['id' => $membershipOne->id]);
    }
}
