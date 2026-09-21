<?php

namespace Tests\Feature\Event;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression test for two N+1s found+fixed in Sprint 13 polish
 * (fr-operational-polish.md): (1) EventPolicy::membershipRole() re-querying
 * organization_users on every single @can() check — the event page calls 8
 * different abilities dozens of times across nested loops; (2)
 * judging_criteria.children not eager-loaded, queried once per criterion.
 * Both scale with the number of rounds/criteria on the page, so a real event
 * with many rounds is what actually catches a regression here — a single
 * round wouldn't show the difference.
 */
class QueryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_show_query_count_does_not_scale_with_rounds_and_criteria(): void
    {
        $owner = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $event = Event::factory()->create(['organization_id' => $organization->id]);

        // 4 categories x 3 rounds x 2 criteria = 12 rounds, 24 criteria —
        // enough that a per-round or per-criterion query would visibly blow
        // up the count if the eager-loading regresses.
        foreach (range(1, 4) as $ci) {
            $category = EventCategory::factory()->create(['event_id' => $event->id]);
            CompetitionEntry::factory()->create(['event_id' => $event->id, 'event_category_id' => $category->id]);

            foreach (range(1, 3) as $ri) {
                $round = JudgingRound::factory()->create(['event_category_id' => $category->id]);
                JudgingCriteria::factory()->create(['judging_round_id' => $round->id, 'minimum_score' => 0, 'maximum_score' => 100]);
                JudgingCriteria::factory()->create(['judging_round_id' => $round->id, 'minimum_score' => 0, 'maximum_score' => 100]);
            }
        }

        DB::enableQueryLog();

        $this->actingAs($owner)->get(route('dashboard.events.show', $event))->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Calibrated against this exact fixture: 20 with both fixes in
        // place, 43 with only the eager load reverted, 119 with only the
        // Policy cache reverted — 30 sits cleanly between "healthy" and
        // either regression, so it catches both without being brittle to
        // small legitimate query count changes.
        $this->assertLessThan(30, $queryCount, "Expected a bounded query count, got {$queryCount} — check EventPolicy::membershipRole() caching and the judging_criteria.children eager load in EventController::show().");
    }
}
