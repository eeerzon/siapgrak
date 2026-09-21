<?php

namespace Tests\Feature\Event;

use App\Enums\OrganizationRole;
use App\Enums\ScheduleSlotType;
use App\Enums\Status;
use App\Models\CompetitionResult;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventScheduleSlot;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F11 discovery: every tab's "reopen the drawer/form on a failed submit"
 * fix (F7/F8/F9/F11) only actually reopens the drawer's own local Alpine
 * state — the OUTER x-data="{ tab: 'overview' }" in events/show.blade.php
 * always defaulted back to Overview on a fresh page load regardless, so the
 * reopened drawer sat correctly-populated but invisible behind a hidden tab
 * panel. Every relevant form now carries a hidden `_tab` field, and the
 * outer tab state reads it back — whitelisted against a fixed set of tab
 * keys, since naively embedding unvalidated old() input inside a
 * single-quoted Alpine x-data string is a JS-string-breakout risk that
 * HTML-entity escaping alone doesn't stop.
 */
class TabRestorationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->event = Event::factory()->create(['organization_id' => $this->organization->id]);
    }

    public function test_page_defaults_to_overview_with_no_errors(): void
    {
        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        $response->assertSee("x-data=\"{ tab: 'overview' }\"", false);
    }

    public function test_failed_category_edit_lands_on_the_categories_tab(): void
    {
        $category = EventCategory::factory()->create(['event_id' => $this->event->id]);

        $this->actingAs($this->owner)->put(route('dashboard.events.categories.update', [$this->event, $category]), [
            '_category_id' => (string) $category->id,
            '_tab' => 'categories',
            'name' => '',
        ])->assertSessionHasErrors('name');

        $page = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));
        $page->assertSee("x-data=\"{ tab: 'categories' }\"", false);
    }

    public function test_failed_schedule_edit_lands_on_the_schedule_tab(): void
    {
        $slot = EventScheduleSlot::factory()->create(['event_id' => $this->event->id]);

        $this->actingAs($this->owner)->put(route('dashboard.events.schedule.update', [$this->event, $slot]), [
            '_slot_id' => (string) $slot->id,
            '_tab' => 'schedule',
            'slot_type' => ScheduleSlotType::Break->value,
            'start_at' => '',
        ])->assertSessionHasErrors('start_at');

        $page = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));
        $page->assertSee("x-data=\"{ tab: 'schedule' }\"", false);
    }

    public function test_failed_criteria_edit_lands_on_the_judging_tab(): void
    {
        $category = EventCategory::factory()->create(['event_id' => $this->event->id]);
        $round = JudgingRound::factory()->create(['event_category_id' => $category->id]);
        $criterion = JudgingCriteria::factory()->create(['judging_round_id' => $round->id, 'minimum_score' => 0, 'maximum_score' => 100]);

        $this->actingAs($this->owner)->put(route('dashboard.judging-criteria.update', $criterion), [
            '_criterion_id' => (string) $criterion->id,
            '_tab' => 'judging',
            'name' => 'X',
            'minimum_score' => 50,
            'maximum_score' => 10,
        ])->assertSessionHasErrors('maximum_score');

        $page = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));
        $page->assertSee("x-data=\"{ tab: 'judging' }\"", false);
    }

    public function test_failed_penalty_update_lands_on_the_results_tab_and_shows_the_error(): void
    {
        $category = EventCategory::factory()->create(['event_id' => $this->event->id]);
        $round = JudgingRound::factory()->create(['event_category_id' => $category->id]);
        $result = CompetitionResult::factory()->create(['event_category_id' => $category->id, 'judging_round_id' => $round->id]);

        $this->actingAs($this->owner)->patch(route('dashboard.results.penalty', $result), [
            '_result_id' => (string) $result->id,
            '_tab' => 'results',
            'penalty' => -5,
        ])->assertSessionHasErrors('penalty');

        $page = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));
        $page->assertSee("x-data=\"{ tab: 'results' }\"", false);
        $page->assertSee('Penalti tidak boleh negatif.');
    }

    public function test_an_unrecognised_tab_value_falls_back_to_overview(): void
    {
        $category = EventCategory::factory()->create(['event_id' => $this->event->id]);

        // A crafted _tab designed to break out of the single-quoted Alpine
        // x-data string — must never reach the rendered attribute verbatim.
        $this->actingAs($this->owner)->put(route('dashboard.events.categories.update', [$this->event, $category]), [
            '_category_id' => (string) $category->id,
            '_tab' => "x'}); alert(1); ({tab:'overview",
            'name' => '',
        ])->assertSessionHasErrors('name');

        $page = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));
        $page->assertSee("x-data=\"{ tab: 'overview' }\"", false);
        $page->assertDontSee('alert(1)');
    }
}
