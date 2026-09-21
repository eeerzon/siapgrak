<?php

namespace Tests\Feature\Judge;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Judge;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessCodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
    }

    public function test_a_new_judge_gets_a_unique_access_code_automatically(): void
    {
        $judge = Judge::factory()->create(['organization_id' => $this->organization->id]);

        $this->assertNotNull($judge->access_code);
        $this->assertSame(8, strlen($judge->access_code));
    }

    public function test_access_codes_are_unique_across_many_judges(): void
    {
        $judges = Judge::factory()->count(20)->create(['organization_id' => $this->organization->id]);

        $this->assertSame(20, $judges->pluck('access_code')->unique()->count());
    }

    public function test_owner_sees_the_access_code_on_the_judge_show_page(): void
    {
        $judge = Judge::factory()->create(['organization_id' => $this->organization->id]);

        $this->actingAs($this->owner)
            ->get(route('dashboard.judges.show', $judge))
            ->assertOk()
            ->assertSee($judge->access_code);
    }

    public function test_owner_can_regenerate_the_access_code(): void
    {
        $judge = Judge::factory()->create(['organization_id' => $this->organization->id]);
        $oldCode = $judge->access_code;

        $this->actingAs($this->owner)
            ->patch(route('dashboard.judges.access-code', $judge))
            ->assertRedirect(route('dashboard.judges.show', $judge));

        $judge->refresh();
        $this->assertNotSame($oldCode, $judge->access_code);
    }

    public function test_member_role_cannot_regenerate_the_access_code(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);
        $judge = Judge::factory()->create(['organization_id' => $this->organization->id]);

        $this->actingAs($member)
            ->patch(route('dashboard.judges.access-code', $judge))
            ->assertForbidden();
    }
}
