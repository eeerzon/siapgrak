<?php

namespace Tests\Feature\Organization;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DB-level constraints, independent of application validation — proves the
 * schema itself (not just the Form Request) refuses invalid states.
 */
class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_public_id_is_unique_at_db_level(): void
    {
        $one = Organization::factory()->create();

        $this->expectException(QueryException::class);

        Organization::factory()->create(['public_id' => $one->public_id]);
    }

    public function test_organization_slug_is_unique_at_db_level(): void
    {
        Organization::factory()->create(['slug' => 'dup-slug']);

        $this->expectException(QueryException::class);

        Organization::factory()->create(['slug' => 'dup-slug']);
    }

    public function test_organization_user_composite_unique_at_db_level(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $organization->users()->attach($user, ['role' => OrganizationRole::Member, 'status' => Status::Active]);

        $this->expectException(QueryException::class);

        // Bypass Eloquent's attach() convenience and insert directly, to prove the
        // constraint lives in the schema, not just in application-level checks.
        \DB::table('organization_users')->insert([
            'organization_id' => $organization->id,
            'user_id' => $user->id_user,
            'role' => OrganizationRole::Owner->value,
            'status' => Status::Active->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_organization_user_requires_existing_organization(): void
    {
        $user = User::factory()->create();

        $this->expectException(QueryException::class);

        \DB::table('organization_users')->insert([
            'organization_id' => 999999,
            'user_id' => $user->id_user,
            'role' => OrganizationRole::Member->value,
            'status' => Status::Active->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_organization_user_requires_existing_user(): void
    {
        $organization = Organization::factory()->create();

        $this->expectException(QueryException::class);

        \DB::table('organization_users')->insert([
            'organization_id' => $organization->id,
            'user_id' => 999999,
            'role' => OrganizationRole::Member->value,
            'status' => Status::Active->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_deleting_organization_cascades_memberships(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $organization->users()->attach($user, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $membershipId = $organization->memberships()->first()->id;

        $organization->delete();

        $this->assertDatabaseMissing('organization_users', ['id' => $membershipId]);
    }
}
