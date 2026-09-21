<?php

namespace Tests\Feature\JudgingCriteria;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Judge;
use App\Models\JudgeAssignment;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F9 — Judging Setup readiness checklist + criteria card grid/drawer
 * (frontend-plan.md §Judging Setup). Same multi-form cross-contamination
 * hazard as F7/F8, but with an extra wrinkle: a page can have SEVERAL
 * rounds, each with its own "add criteria" form, so isolating just by
 * criterion id isn't enough for the create case — the create forms also
 * need a `_round_id` discriminator.
 */
class CriteriaDrawerTest extends TestCase
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

    public function test_readiness_checklist_reflects_an_empty_category(): void
    {
        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        $checklist = $response->viewData('judgingReadiness')[$this->category->id];
        $byLabel = collect($checklist)->keyBy('label');

        $this->assertFalse($byLabel['Round belum dibuat']['ok']);
        $this->assertFalse($byLabel['0 kriteria']['ok']);
        $this->assertFalse($byLabel['0 juri']['ok']);
    }

    public function test_readiness_checklist_reflects_a_fully_set_up_category(): void
    {
        $round = JudgingRound::factory()->create(['event_category_id' => $this->category->id]);
        JudgingCriteria::factory()->count(2)->create(['judging_round_id' => $round->id, 'minimum_score' => 0, 'maximum_score' => 100]);
        $judge = Judge::factory()->create(['organization_id' => $this->organization->id]);
        JudgeAssignment::factory()->create(['judging_round_id' => $round->id, 'judge_id' => $judge->id]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        $checklist = $response->viewData('judgingReadiness')[$this->category->id];
        $byLabel = collect($checklist)->keyBy('label');

        $this->assertTrue($byLabel['Round configured']['ok']);
        $this->assertTrue($byLabel['2 kriteria']['ok']);
        $this->assertTrue($byLabel['1 juri']['ok']);
    }

    public function test_edit_button_and_prefilled_form_are_rendered_per_criterion(): void
    {
        $round = JudgingRound::factory()->create(['event_category_id' => $this->category->id]);
        $criterion = JudgingCriteria::factory()->create([
            'judging_round_id' => $round->id, 'name' => 'PBB', 'minimum_score' => 0, 'maximum_score' => 100,
        ]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        $response->assertOk();
        $response->assertSee('editing = '.$criterion->id, false);
        $response->assertSee('value="PBB"', false);
    }

    public function test_failed_edit_on_one_criterion_does_not_leak_into_a_sibling_criterions_form(): void
    {
        $round = JudgingRound::factory()->create(['event_category_id' => $this->category->id]);
        $criterionA = JudgingCriteria::factory()->create(['judging_round_id' => $round->id, 'name' => 'Criterion A', 'minimum_score' => 0, 'maximum_score' => 100]);
        $criterionB = JudgingCriteria::factory()->create(['judging_round_id' => $round->id, 'name' => 'Criterion B', 'minimum_score' => 0, 'maximum_score' => 100]);

        $this->actingAs($this->owner)->put(route('dashboard.judging-criteria.update', $criterionA), [
            '_criterion_id' => (string) $criterionA->id,
            'name' => 'REJECTED NAME',
            'minimum_score' => 50,
            'maximum_score' => 10,
        ])->assertSessionHasErrors('maximum_score');

        $page = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        $bFormAction = route('dashboard.judging-criteria.update', $criterionB);
        preg_match_all('#<form[^>]*action="'.preg_quote($bFormAction, '#').'".*?</form>#s', $page->getContent(), $matches);
        $bFormHtml = collect($matches[0])->first(fn ($form) => str_contains($form, '_method" value="PUT"'));
        $this->assertNotNull($bFormHtml, "Could not locate criterion B's edit form in the response.");

        $this->assertStringContainsString('value="Criterion B"', $bFormHtml);
        $this->assertStringNotContainsString('REJECTED NAME', $bFormHtml);
    }

    public function test_failed_create_in_one_round_does_not_leak_into_a_sibling_rounds_create_form(): void
    {
        $roundA = JudgingRound::factory()->create(['event_category_id' => $this->category->id, 'name' => 'Round A']);
        $roundB = JudgingRound::factory()->create(['event_category_id' => $this->category->id, 'name' => 'Round B']);

        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.criteria.store', $roundA), [
            '_criterion_id' => '',
            '_round_id' => (string) $roundA->id,
            'name' => 'REJECTED CRITERION',
            'minimum_score' => 50,
            'maximum_score' => 10,
        ])->assertSessionHasErrors('maximum_score');

        $page = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        $roundBFormAction = route('dashboard.judging-rounds.criteria.store', $roundB);
        preg_match('#<form[^>]*action="'.preg_quote($roundBFormAction, '#').'".*?</form>#s', $page->getContent(), $matches);
        $this->assertNotEmpty($matches, "Could not locate round B's create-criteria form in the response.");

        $this->assertStringNotContainsString('REJECTED CRITERION', $matches[0]);
    }

    public function test_failed_edit_does_not_leak_into_any_rounds_create_form(): void
    {
        $round = JudgingRound::factory()->create(['event_category_id' => $this->category->id]);
        $criterion = JudgingCriteria::factory()->create(['judging_round_id' => $round->id, 'minimum_score' => 0, 'maximum_score' => 100]);

        $this->actingAs($this->owner)->put(route('dashboard.judging-criteria.update', $criterion), [
            '_criterion_id' => (string) $criterion->id,
            'name' => 'REJECTED EDIT NAME',
            'minimum_score' => 50,
            'maximum_score' => 10,
        ])->assertSessionHasErrors('maximum_score');

        $page = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        $createFormAction = route('dashboard.judging-rounds.criteria.store', $round);
        preg_match('#<form[^>]*action="'.preg_quote($createFormAction, '#').'".*?</form>#s', $page->getContent(), $matches);
        $this->assertNotEmpty($matches, 'Could not locate the create-criteria form in the response.');

        $this->assertStringNotContainsString('REJECTED EDIT NAME', $matches[0]);
    }
}
