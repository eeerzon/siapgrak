<?php

namespace Tests\Feature\Judge;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Judge;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateJudgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_member_can_create_judge_in_own_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user, ['role' => OrganizationRole::Member, 'status' => Status::Active]);

        $response = $this->actingAs($user)->post(route('dashboard.judges.store'), [
            'organization_id' => $organization->id,
            'name' => 'Kolonel Purn. Wibowo',
        ]);

        $judge = Judge::where('name', 'Kolonel Purn. Wibowo')->firstOrFail();
        $response->assertRedirect(route('dashboard.judges.show', $judge));
        $this->assertSame($organization->id, $judge->organization_id);
        $this->assertStringStartsWith('judge_', $judge->public_id);
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $this->actingAs($user)
            ->post(route('dashboard.judges.store'), ['organization_id' => $organization->id])
            ->assertSessionHasErrors('name');
    }

    public function test_organization_id_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('dashboard.judges.store'), ['name' => 'Orphan Judge'])
            ->assertSessionHasErrors('organization_id');

        $this->assertDatabaseMissing('judges', ['name' => 'Orphan Judge']);
    }

    public function test_user_cannot_attach_judge_to_foreign_organization(): void
    {
        $user = User::factory()->create();
        $ownOrganization = Organization::factory()->create();
        $user->organizations()->attach($ownOrganization, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $foreignOrganization = Organization::factory()->create();

        $this->actingAs($user)
            ->post(route('dashboard.judges.store'), [
                'organization_id' => $foreignOrganization->id,
                'name' => 'Hijacked Judge',
            ])
            ->assertSessionHasErrors('organization_id');

        $this->assertDatabaseMissing('judges', ['name' => 'Hijacked Judge']);
    }

    public function test_guest_cannot_create_judge(): void
    {
        $this->post(route('dashboard.judges.store'), ['name' => 'Guest Judge'])
            ->assertRedirect(route('login'));
    }
}
