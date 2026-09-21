<?php

namespace Tests\Feature\Judge;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Judge;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateJudgeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Judge}
     */
    protected function judgeFor(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user, ['role' => $role, 'status' => Status::Active]);
        $judge = Judge::factory()->create(['organization_id' => $organization->id]);

        return [$user, $judge];
    }

    public function test_owner_can_update_judge(): void
    {
        [$owner, $judge] = $this->judgeFor(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->put(route('dashboard.judges.update', $judge), ['name' => 'Renamed Judge'])
            ->assertRedirect(route('dashboard.judges.show', $judge));

        $this->assertSame('Renamed Judge', $judge->fresh()->name);
    }

    public function test_member_role_cannot_update_judge(): void
    {
        [$member, $judge] = $this->judgeFor(OrganizationRole::Member);

        $this->actingAs($member)
            ->put(route('dashboard.judges.update', $judge), ['name' => 'Should Not Save'])
            ->assertForbidden();

        $this->assertDatabaseMissing('judges', ['name' => 'Should Not Save']);
    }

    public function test_organization_id_cannot_be_changed_via_update(): void
    {
        [$owner, $judge] = $this->judgeFor(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();

        $this->actingAs($owner)->put(route('dashboard.judges.update', $judge), [
            'name' => $judge->name,
            'organization_id' => $otherOrg->id,
        ]);

        $this->assertSame($judge->organization_id, $judge->fresh()->organization_id);
    }
}
