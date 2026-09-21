<?php

namespace Tests\Feature\Event;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F7 — Categories card list + drawer create/edit (frontend-plan.md
 * §Categories). The interesting risk here is architectural: one create form
 * plus one edit form PER category all render on the same page, sharing
 * Laravel's single global old()/$errors bag by field name — a failed submit
 * on one category must never bleed its rejected values into another
 * category's (hidden) form on the same render.
 */
class CategoryDrawerTest extends TestCase
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

    public function test_card_list_shows_registered_team_count(): void
    {
        $category = EventCategory::factory()->create(['event_id' => $this->event->id, 'name' => 'SMA Putra']);
        Registration::factory()->count(2)->create(['event_id' => $this->event->id, 'event_category_id' => $category->id]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        $response->assertOk();
        $response->assertSeeInOrder(['SMA Putra', 'Tim terdaftar', '2']);
    }

    public function test_failed_edit_does_not_reopen_the_create_form(): void
    {
        $category = EventCategory::factory()->create(['event_id' => $this->event->id, 'name' => 'SMA Putra']);

        $response = $this->from(route('dashboard.events.show', $this->event))
            ->actingAs($this->owner)
            ->put(route('dashboard.events.categories.update', [$this->event, $category]), [
                '_category_id' => (string) $category->id,
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');
        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        // The edit form for THIS category re-opens with the rejected (empty)
        // value visible via old() — asserted indirectly: the drawer's
        // `editing` Alpine state must target this category's id, not null
        // (which would mean the create form reopened instead).
        $response->assertSee("editing: {$category->id} }", false);
    }

    public function test_failed_edit_on_one_category_does_not_leak_into_a_sibling_categorys_form(): void
    {
        $categoryA = EventCategory::factory()->create(['event_id' => $this->event->id, 'name' => 'Category A', 'code' => 'CODE-A']);
        $categoryB = EventCategory::factory()->create(['event_id' => $this->event->id, 'name' => 'Category B', 'code' => 'CODE-B']);

        // Fail A's edit with a name that collides with B's unique code, and
        // an obviously-wrong attempted name — if these bleed into B's form,
        // B's rendered <input name="name" value="..."> would show them.
        $response = $this->actingAs($this->owner)->put(route('dashboard.events.categories.update', [$this->event, $categoryA]), [
            '_category_id' => (string) $categoryA->id,
            'name' => 'REJECTED ATTEMPT',
            'code' => 'CODE-B',
        ]);
        $response->assertSessionHasErrors('code');

        $page = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        // B's own edit form must still show B's real persisted values — and
        // specifically must NOT contain A's rejected input. A's own form is
        // legitimately allowed to show "REJECTED ATTEMPT" (it's the form
        // that actually failed), so isolate B's <form>...</form> block
        // rather than asserting on the whole page.
        // The same URL serves both the destroy (DELETE) and edit (PUT) forms
        // for a category — disambiguate by requiring the PUT method spoof.
        $bFormAction = route('dashboard.events.categories.update', [$this->event, $categoryB]);
        preg_match_all('#<form[^>]*action="'.preg_quote($bFormAction, '#').'".*?</form>#s', $page->getContent(), $matches);
        $bFormHtml = collect($matches[0])->first(fn ($form) => str_contains($form, '_method" value="PUT"'));
        $this->assertNotNull($bFormHtml, 'Could not locate category B\'s edit form in the response.');
        $this->assertStringContainsString('value="Category B"', $bFormHtml);
        $this->assertStringNotContainsString('REJECTED ATTEMPT', $bFormHtml);
    }

    public function test_failed_create_does_not_leak_into_any_edit_form(): void
    {
        $category = EventCategory::factory()->create(['event_id' => $this->event->id, 'name' => 'Real Category']);

        $this->actingAs($this->owner)->post(route('dashboard.events.categories.store', $this->event), [
            '_category_id' => '',
            'name' => '',
            'min_members' => 20,
            'max_members' => 5,
        ])->assertSessionHasErrors(['name', 'max_members']);

        $page = $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));

        $page->assertSee("value=\"{$category->name}\"", false);
    }
}
