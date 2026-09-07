<?php

namespace Tests\Feature\Organization;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationType;
use App\Enums\Status;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_organization(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $response = $this->actingAs($owner)->put(route('dashboard.organizations.update', $organization), [
            'name' => 'Updated Name',
            'slug' => $organization->slug,
            'type' => OrganizationType::School->value,
        ]);

        $response->assertRedirect(route('dashboard.organizations.show', $organization));
        $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'name' => 'Updated Name']);
    }

    public function test_admin_can_update_organization(): void
    {
        $admin = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($admin, ['role' => OrganizationRole::Admin, 'status' => Status::Active]);

        $response = $this->actingAs($admin)->put(route('dashboard.organizations.update', $organization), [
            'name' => 'Updated By Admin',
            'slug' => $organization->slug,
            'type' => $organization->type->value,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'name' => 'Updated By Admin']);
    }

    public function test_member_role_cannot_update_organization(): void
    {
        $member = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);

        $response = $this->actingAs($member)->put(route('dashboard.organizations.update', $organization), [
            'name' => 'Should Not Change',
            'slug' => $organization->slug,
            'type' => $organization->type->value,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('organizations', ['name' => 'Should Not Change']);
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        $owner = User::factory()->create();
        $organizationA = Organization::factory()->create(['slug' => 'taken-slug']);
        $organizationB = Organization::factory()->create();
        $organizationB->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $response = $this->actingAs($owner)->put(route('dashboard.organizations.update', $organizationB), [
            'name' => $organizationB->name,
            'slug' => 'taken-slug',
            'type' => $organizationB->type->value,
        ]);

        $response->assertSessionHasErrors('slug');
        $this->assertDatabaseMissing('organizations', ['id' => $organizationB->id, 'slug' => 'taken-slug']);
    }

    public function test_status_field_cannot_be_mass_assigned_through_update_form(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $this->assertSame(Status::Active, $organization->fresh()->status);

        $this->actingAs($owner)->put(route('dashboard.organizations.update', $organization), [
            'name' => $organization->name,
            'slug' => $organization->slug,
            'type' => $organization->type->value,
            'status' => 'inactive', // not in the request's validated() rules — must be ignored
        ]);

        $this->assertSame(Status::Active, $organization->fresh()->status);
    }
}
