<?php

namespace Tests\Feature\Organization;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationType;
use App\Enums\Status;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_organization(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('dashboard.organizations.store'), [
            'name' => 'FORBASI Kota Bandung',
            'type' => OrganizationType::Association->value,
        ]);

        $organization = Organization::where('name', 'FORBASI Kota Bandung')->firstOrFail();

        $response->assertRedirect(route('dashboard.organizations.show', $organization));
        $this->assertDatabaseHas('organizations', ['name' => 'FORBASI Kota Bandung']);
    }

    public function test_creator_becomes_owner_in_same_transaction(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('dashboard.organizations.store'), [
            'name' => 'FORBASI Kota Depok',
            'type' => OrganizationType::Association->value,
        ]);

        $organization = Organization::where('name', 'FORBASI Kota Depok')->firstOrFail();

        $this->assertDatabaseHas('organization_users', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => OrganizationRole::Owner->value,
            'status' => Status::Active->value,
        ]);
    }

    public function test_guest_cannot_create_organization(): void
    {
        $response = $this->post(route('dashboard.organizations.store'), [
            'name' => 'Should Not Exist',
            'type' => OrganizationType::Association->value,
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseMissing('organizations', ['name' => 'Should Not Exist']);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('dashboard.organizations.store'), [
            'type' => OrganizationType::Association->value,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_invalid_type_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('dashboard.organizations.store'), [
            'name' => 'Invalid Type Org',
            'type' => 'not-a-real-type',
        ]);

        $response->assertSessionHasErrors('type');
        $this->assertDatabaseMissing('organizations', ['name' => 'Invalid Type Org']);
    }
}
