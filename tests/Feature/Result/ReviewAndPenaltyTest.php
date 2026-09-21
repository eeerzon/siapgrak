<?php

namespace Tests\Feature\Result;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\CompetitionResult;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F11 cross-check against frontend-plan.md §Results — the spec calls for a
 * [Review Scores] button (added as a jump to the existing Judging tab
 * score-sheet list rather than duplicating it) and closes a silent-failure
 * gap: the per-row penalty form had no @error('penalty') display at all,
 * and since every result row shares one <form name="penalty">, a naive fix
 * would leak one row's error onto every other row (same hazard as F7/F8/F9).
 */
class ReviewAndPenaltyTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected Event $event;

    protected EventCategory $category;

    protected JudgingRound $round;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $this->category = EventCategory::factory()->create(['event_id' => $this->event->id]);
        $this->round = JudgingRound::factory()->create(['event_category_id' => $this->category->id]);
    }

    public function test_review_scores_button_is_rendered_for_a_round_with_results(): void
    {
        CompetitionResult::factory()->create(['event_category_id' => $this->category->id, 'judging_round_id' => $this->round->id]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        $response->assertOk();
        $response->assertSee('Review Skor Juri');
    }

    public function test_invalid_penalty_shows_an_error_message(): void
    {
        $result = CompetitionResult::factory()->create(['event_category_id' => $this->category->id, 'judging_round_id' => $this->round->id]);

        $this->actingAs($this->owner)->patch(route('dashboard.results.penalty', $result), [
            '_result_id' => (string) $result->id,
            '_tab' => 'results',
            'penalty' => -1,
        ])->assertSessionHasErrors('penalty');

        $this->actingAs($this->owner)
            ->get(route('dashboard.events.show', $this->event))
            ->assertSee('Penalti tidak boleh negatif.');
    }

    public function test_failed_penalty_on_one_result_does_not_leak_into_a_sibling_results_field(): void
    {
        $resultA = CompetitionResult::factory()->create(['event_category_id' => $this->category->id, 'judging_round_id' => $this->round->id]);
        $resultB = CompetitionResult::factory()->create(['event_category_id' => $this->category->id, 'judging_round_id' => $this->round->id, 'penalty_score' => 2.5]);

        $this->actingAs($this->owner)->patch(route('dashboard.results.penalty', $resultA), [
            '_result_id' => (string) $resultA->id,
            '_tab' => 'results',
            'penalty' => -1,
        ])->assertSessionHasErrors('penalty');

        $page = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        // Capture through the enclosing </td>, not just </form> — the error
        // message renders as a sibling <p> after the form, inside the same
        // table cell, so stopping at </form> would miss it either way.
        $bFormAction = route('dashboard.results.penalty', $resultB);
        preg_match('#<form[^>]*action="'.preg_quote($bFormAction, '#').'".*?</td>#s', $page->getContent(), $matches);
        $this->assertNotEmpty($matches, "Could not locate result B's penalty cell in the response.");

        $this->assertStringNotContainsString('Penalti tidak boleh negatif.', $matches[0]);
        $this->assertStringContainsString('value="2.500"', $matches[0]);
    }
}
