<?php

namespace Tests\Feature\Judge;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Judge;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Adversarial: "How can User A see or edit Judge B's data?" Mirrors
 * Team/TenantIsolationTest — same class of gap that motivated adding
 * judges.organization_id in the first place (fr-judging-setup.md Keputusan A).
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected Judge $judgeA;

    protected User $userB;

    protected Judge $judgeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create();
        $orgA = Organization::factory()->create();
        $orgA->users()->attach($this->userA, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->judgeA = Judge::factory()->create(['organization_id' => $orgA->id]);

        $this->userB = User::factory()->create();
        $orgB = Organization::factory()->create();
        $orgB->users()->attach($this->userB, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->judgeB = Judge::factory()->create(['organization_id' => $orgB->id]);
    }

    public function test_user_a_cannot_view_judge_b(): void
    {
        $this->actingAs($this->userA)->get(route('dashboard.judges.show', $this->judgeB))->assertForbidden();
    }

    public function test_user_a_cannot_open_edit_form_of_judge_b(): void
    {
        $this->actingAs($this->userA)->get(route('dashboard.judges.edit', $this->judgeB))->assertForbidden();
    }

    public function test_user_a_cannot_update_judge_b_via_manipulated_payload(): void
    {
        $this->actingAs($this->userA)
            ->put(route('dashboard.judges.update', $this->judgeB), ['name' => 'Hijacked Judge B'])
            ->assertForbidden();

        $this->assertDatabaseMissing('judges', ['name' => 'Hijacked Judge B']);
    }

    public function test_judge_index_only_shows_judges_from_own_organizations(): void
    {
        $response = $this->actingAs($this->userA)->get(route('dashboard.judges.index'));

        $response->assertOk();
        $response->assertSee($this->judgeA->name);
        $response->assertDontSee($this->judgeB->name);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard.judges.index'))->assertRedirect(route('login'));
        $this->get(route('dashboard.judges.show', $this->judgeA))->assertRedirect(route('login'));
    }
}
