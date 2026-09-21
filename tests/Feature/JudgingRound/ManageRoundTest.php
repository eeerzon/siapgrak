<?php

namespace Tests\Feature\JudgingRound;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Judge;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\Score;
use App\Models\ScoreSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageRoundTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected Event $event;

    protected EventCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $this->category = EventCategory::factory()->create(['event_id' => $this->event->id]);
    }

    public function test_owner_can_add_a_round(): void
    {
        $this->actingAs($this->owner)
            ->post(route('dashboard.events.categories.rounds.store', [$this->event, $this->category]), [
                'name' => 'Final',
                'weight' => 100,
            ])
            ->assertRedirect(route('dashboard.events.show', $this->event));

        $this->assertDatabaseHas('judging_rounds', ['event_category_id' => $this->category->id, 'name' => 'Final']);
    }

    public function test_category_from_a_different_event_is_rejected(): void
    {
        $foreignCategory = EventCategory::factory()->create();

        $this->actingAs($this->owner)
            ->post(route('dashboard.events.categories.rounds.store', [$this->event, $foreignCategory]), ['name' => 'Final'])
            ->assertNotFound();
    }

    public function test_owner_can_update_and_delete_round(): void
    {
        $round = JudgingRound::factory()->create(['event_category_id' => $this->category->id, 'name' => 'Old']);

        $this->actingAs($this->owner)->put(route('dashboard.judging-rounds.update', $round), ['name' => 'New Name']);
        $this->assertSame('New Name', $round->fresh()->name);

        $this->actingAs($this->owner)->delete(route('dashboard.judging-rounds.destroy', $round));
        $this->assertDatabaseMissing('judging_rounds', ['id' => $round->id]);
    }

    /**
     * Guard added Sprint 9 (fr-scoring.md Keputusan H) — a round with saved
     * scores is competition history, not disposable config.
     */
    public function test_a_round_with_saved_scores_cannot_be_deleted(): void
    {
        $round = JudgingRound::factory()->create(['event_category_id' => $this->category->id]);
        $criteria = JudgingCriteria::factory()->create(['judging_round_id' => $round->id, 'minimum_score' => 0, 'maximum_score' => 100]);
        $judge = Judge::factory()->create(['organization_id' => $this->organization->id]);
        $entry = CompetitionEntry::factory()->create(['event_id' => $this->event->id, 'event_category_id' => $this->category->id]);
        $sheet = ScoreSheet::factory()->create([
            'judging_round_id' => $round->id,
            'competition_entry_id' => $entry->id,
            'judge_id' => $judge->id,
        ]);
        Score::factory()->create(['score_sheet_id' => $sheet->id, 'judging_criteria_id' => $criteria->id, 'score' => 80]);

        $this->actingAs($this->owner)
            ->delete(route('dashboard.judging-rounds.destroy', $round))
            ->assertRedirect(route('dashboard.events.show', $this->event));

        $this->assertDatabaseHas('judging_rounds', ['id' => $round->id]);
    }

    public function test_member_role_can_view_but_not_mutate(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);
        $round = JudgingRound::factory()->create(['event_category_id' => $this->category->id]);

        $this->actingAs($member)->get(route('dashboard.events.show', $this->event))->assertOk();

        $this->actingAs($member)
            ->post(route('dashboard.events.categories.rounds.store', [$this->event, $this->category]), ['name' => 'Nope'])
            ->assertForbidden();

        $this->actingAs($member)
            ->delete(route('dashboard.judging-rounds.destroy', $round))
            ->assertForbidden();
    }
}
