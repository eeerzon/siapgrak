<?php

namespace Tests\Feature\ScheduleSlot;

use App\Enums\OrganizationRole;
use App\Enums\ScheduleSlotType;
use App\Enums\Status;
use App\Models\Event;
use App\Models\EventScheduleSlot;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F8 — added the missing "Edit Slot" action (frontend-plan.md §Schedule);
 * update() already existed server-side with no UI for it. Same multi-form
 * cross-contamination risk as F7 Categories applies here (1 inline create
 * form + N edit forms in a drawer, all sharing Laravel's global old()/
 * $errors bag by field name) — verified isolated the same way.
 */
class ScheduleDrawerTest extends TestCase
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

    public function test_edit_button_and_prefilled_form_are_rendered_for_each_slot(): void
    {
        $slot = EventScheduleSlot::factory()->create([
            'event_id' => $this->event->id,
            'title' => 'Opening Ceremony',
            'slot_type' => ScheduleSlotType::Ceremony,
        ]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        $response->assertOk();
        $response->assertSee('editing = '.$slot->id, false);
        $response->assertSee('value="Opening Ceremony"', false);
    }

    public function test_failed_edit_reopens_the_drawer_on_the_right_slot_not_the_create_form(): void
    {
        $slot = EventScheduleSlot::factory()->create(['event_id' => $this->event->id]);

        $this->actingAs($this->owner)->put(route('dashboard.events.schedule.update', [$this->event, $slot]), [
            '_slot_id' => (string) $slot->id,
            'slot_type' => ScheduleSlotType::Break->value,
            'start_at' => '',
        ])->assertSessionHasErrors('start_at');

        $page = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        $page->assertSee("editing: {$slot->id} }", false);
    }

    public function test_failed_edit_on_one_slot_does_not_leak_into_a_sibling_slots_form(): void
    {
        $slotA = EventScheduleSlot::factory()->create(['event_id' => $this->event->id, 'title' => 'Slot A']);
        $slotB = EventScheduleSlot::factory()->create(['event_id' => $this->event->id, 'title' => 'Slot B']);

        $this->actingAs($this->owner)->put(route('dashboard.events.schedule.update', [$this->event, $slotA]), [
            '_slot_id' => (string) $slotA->id,
            'title' => 'REJECTED TITLE',
            'slot_type' => ScheduleSlotType::Break->value,
            'start_at' => '',
        ])->assertSessionHasErrors('start_at');

        $page = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        // The same URL serves both the destroy (DELETE) and edit (PUT) forms
        // for a slot — disambiguate by requiring the PUT method spoof.
        $bFormAction = route('dashboard.events.schedule.update', [$this->event, $slotB]);
        preg_match_all('#<form[^>]*action="'.preg_quote($bFormAction, '#').'".*?</form>#s', $page->getContent(), $matches);
        $bFormHtml = collect($matches[0])->first(fn ($form) => str_contains($form, '_method" value="PUT"'));
        $this->assertNotNull($bFormHtml, "Could not locate slot B's edit form in the response.");
        $this->assertStringContainsString('value="Slot B"', $bFormHtml);
        $this->assertStringNotContainsString('REJECTED TITLE', $bFormHtml);
    }

    public function test_failed_edit_does_not_leak_into_the_inline_create_form(): void
    {
        $slot = EventScheduleSlot::factory()->create(['event_id' => $this->event->id]);

        $this->actingAs($this->owner)->put(route('dashboard.events.schedule.update', [$this->event, $slot]), [
            '_slot_id' => (string) $slot->id,
            'title' => 'REJECTED TITLE',
            'slot_type' => ScheduleSlotType::Break->value,
            'start_at' => '',
        ])->assertSessionHasErrors('start_at');

        $page = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        $createFormAction = route('dashboard.events.schedule.store', $this->event);
        preg_match('#<form[^>]*action="'.preg_quote($createFormAction, '#').'".*?</form>#s', $page->getContent(), $matches);
        $this->assertNotEmpty($matches, 'Could not locate the create form in the response.');

        $this->assertStringNotContainsString('REJECTED TITLE', $matches[0]);
    }
}
