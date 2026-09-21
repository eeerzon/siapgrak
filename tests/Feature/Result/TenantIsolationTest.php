<?php

namespace Tests\Feature\Result;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Award;
use App\Models\CompetitionEntry;
use App\Models\CompetitionResult;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $ownerA;

    protected Event $eventB;

    protected JudgingRound $roundB;

    protected CompetitionResult $resultB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerA = User::factory()->create();
        $orgA = Organization::factory()->create();
        $orgA->users()->attach($this->ownerA, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $orgB = Organization::factory()->create();
        $this->eventB = Event::factory()->create(['organization_id' => $orgB->id]);
        $categoryB = EventCategory::factory()->create(['event_id' => $this->eventB->id]);
        $this->roundB = JudgingRound::factory()->create(['event_category_id' => $categoryB->id]);
        $entryB = CompetitionEntry::factory()->create([
            'event_id' => $this->eventB->id,
            'event_category_id' => $categoryB->id,
        ]);
        $this->resultB = CompetitionResult::factory()->create([
            'event_category_id' => $categoryB->id,
            'judging_round_id' => $this->roundB->id,
            'competition_entry_id' => $entryB->id,
        ]);
    }

    public function test_owner_of_org_a_cannot_trigger_calculation_for_round_b(): void
    {
        $this->actingAs($this->ownerA)
            ->post(route('dashboard.judging-rounds.results.calculate', $this->roundB))
            ->assertForbidden();
    }

    public function test_owner_of_org_a_cannot_edit_penalty_of_a_result_in_org_b(): void
    {
        $this->actingAs($this->ownerA)
            ->patch(route('dashboard.results.penalty', $this->resultB), ['penalty' => 10])
            ->assertForbidden();

        $this->assertSame('0.000', $this->resultB->fresh()->penalty_score);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('dashboard.judging-rounds.results.calculate', $this->roundB))
            ->assertRedirect(route('login'));
    }

    public function test_owner_of_org_a_cannot_finalize_publish_or_reopen_round_b(): void
    {
        $this->actingAs($this->ownerA)
            ->post(route('dashboard.judging-rounds.results.finalize', $this->roundB))
            ->assertForbidden();

        $this->actingAs($this->ownerA)
            ->post(route('dashboard.judging-rounds.results.publish', $this->roundB))
            ->assertForbidden();

        $this->actingAs($this->ownerA)
            ->post(route('dashboard.judging-rounds.results.reopen', $this->roundB), ['reason' => 'x'])
            ->assertForbidden();

        $this->assertSame('draft', $this->resultB->fresh()->status->value);
    }

    public function test_owner_of_org_a_cannot_manage_awards_of_event_b(): void
    {
        $award = Award::factory()->create(['event_id' => $this->eventB->id]);

        $this->actingAs($this->ownerA)
            ->post(route('dashboard.events.awards.store', $this->eventB), ['name' => 'x', 'award_type' => 'x'])
            ->assertForbidden();

        $this->actingAs($this->ownerA)
            ->put(route('dashboard.awards.update', $award), ['name' => 'Hijacked', 'award_type' => 'x'])
            ->assertForbidden();

        $this->actingAs($this->ownerA)
            ->delete(route('dashboard.awards.destroy', $award))
            ->assertForbidden();

        $this->assertDatabaseHas('awards', ['id' => $award->id]);
    }
}
