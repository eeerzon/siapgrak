<?php

namespace Tests\Feature\Team;

use App\Models\Organization;
use App\Models\Team;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_public_id_is_unique_at_db_level(): void
    {
        $one = Team::factory()->create();

        $this->expectException(QueryException::class);

        Team::factory()->create(['public_id' => $one->public_id]);
    }

    public function test_team_slug_is_unique_at_db_level(): void
    {
        Team::factory()->create(['slug' => 'dup-team-slug']);

        $this->expectException(QueryException::class);

        Team::factory()->create(['slug' => 'dup-team-slug']);
    }

    public function test_team_requires_existing_organization_when_set(): void
    {
        $this->expectException(QueryException::class);

        \DB::table('teams')->insert([
            'public_id' => 'team_test123',
            'organization_id' => 999999,
            'name' => 'Ghost Org Team',
            'slug' => 'ghost-org-team',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_team_can_be_created_without_organization_at_db_level(): void
    {
        // The column itself is nullable (fr-team-directory.md design decision) —
        // application validation is what prevents this via the UI, not the DB.
        $team = Team::factory()->create(['organization_id' => null]);

        $this->assertNull($team->fresh()->organization_id);
    }

    public function test_deleting_organization_with_teams_is_restricted(): void
    {
        $organization = Organization::factory()->create();
        Team::factory()->create(['organization_id' => $organization->id]);

        $this->expectException(QueryException::class);

        $organization->delete();
    }

    public function test_deleting_team_cascades_members(): void
    {
        $team = Team::factory()->create();
        $member = $team->members()->create(['name' => 'Roster Member']);

        $team->delete();

        $this->assertDatabaseMissing('team_members', ['id' => $member->id]);
    }
}
