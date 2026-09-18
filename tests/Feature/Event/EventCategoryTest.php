<?php

namespace Tests\Feature\Event;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCategoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Event}
     */
    protected function eventFor(OrganizationRole $role): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user, ['role' => $role, 'status' => Status::Active]);
        $event = Event::factory()->create(['organization_id' => $organization->id]);

        return [$user, $event];
    }

    public function test_owner_can_add_category(): void
    {
        [$owner, $event] = $this->eventFor(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->post(route('dashboard.events.categories.store', $event), [
                'name' => 'SMA Putra',
                'code' => 'SMA-PA',
                'quota' => 24,
                'min_members' => 13,
                'max_members' => 16,
            ])
            ->assertRedirect(route('dashboard.events.show', $event));

        $this->assertDatabaseHas('event_categories', [
            'event_id' => $event->id,
            'name' => 'SMA Putra',
            'code' => 'SMA-PA',
            'status' => Status::Active->value,
        ]);
    }

    public function test_member_can_view_but_not_mutate_categories(): void
    {
        [$member, $event] = $this->eventFor(OrganizationRole::Member);

        $this->actingAs($member)->get(route('dashboard.events.show', $event))->assertOk();

        $this->actingAs($member)
            ->post(route('dashboard.events.categories.store', $event), ['name' => 'Nope'])
            ->assertForbidden();

        $this->assertDatabaseMissing('event_categories', ['name' => 'Nope']);
    }

    public function test_duplicate_code_within_event_is_rejected_but_reusable_across_events(): void
    {
        [$owner, $event] = $this->eventFor(OrganizationRole::Owner);
        $event->categories()->create(['name' => 'First', 'code' => 'DUP']);

        $this->actingAs($owner)
            ->post(route('dashboard.events.categories.store', $event), ['name' => 'Second', 'code' => 'DUP'])
            ->assertSessionHasErrors('code');

        // same code, different event -> fine
        $otherEvent = Event::factory()->create(['organization_id' => $event->organization_id]);
        $this->actingAs($owner)
            ->post(route('dashboard.events.categories.store', $otherEvent), ['name' => 'Elsewhere', 'code' => 'DUP'])
            ->assertSessionHasNoErrors();
    }

    public function test_max_members_below_min_is_rejected(): void
    {
        [$owner, $event] = $this->eventFor(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->post(route('dashboard.events.categories.store', $event), [
                'name' => 'Bad Range',
                'min_members' => 16,
                'max_members' => 12,
            ])
            ->assertSessionHasErrors('max_members');
    }

    public function test_owner_can_update_and_delete_category(): void
    {
        [$owner, $event] = $this->eventFor(OrganizationRole::Owner);
        $category = $event->categories()->create(['name' => 'Old Name']);

        $this->actingAs($owner)->put(route('dashboard.events.categories.update', [$event, $category]), [
            'name' => 'New Name',
        ])->assertRedirect(route('dashboard.events.show', $event));
        $this->assertDatabaseHas('event_categories', ['id' => $category->id, 'name' => 'New Name']);

        $this->actingAs($owner)->delete(route('dashboard.events.categories.destroy', [$event, $category]));
        $this->assertDatabaseMissing('event_categories', ['id' => $category->id]);
    }

    public function test_category_with_registrations_cannot_be_deleted(): void
    {
        [$owner, $event] = $this->eventFor(OrganizationRole::Owner);
        $category = $event->categories()->create(['name' => 'Has Participants']);
        Registration::factory()->create([
            'event_id' => $event->id,
            'event_category_id' => $category->id,
        ]);

        $this->actingAs($owner)
            ->delete(route('dashboard.events.categories.destroy', [$event, $category]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('event_categories', ['id' => $category->id]);
    }

    public function test_category_of_event_b_not_reachable_via_event_a_url(): void
    {
        [$owner, $eventA] = $this->eventFor(OrganizationRole::Owner);
        $eventB = Event::factory()->create(['organization_id' => $eventA->organization_id]);
        $categoryInB = $eventB->categories()->create(['name' => 'Belongs to B']);

        $this->actingAs($owner)
            ->put(route('dashboard.events.categories.update', [$eventA, $categoryInB]), ['name' => 'Hijack'])
            ->assertNotFound();

        $this->actingAs($owner)
            ->delete(route('dashboard.events.categories.destroy', [$eventA, $categoryInB]))
            ->assertNotFound();

        $this->assertDatabaseHas('event_categories', ['id' => $categoryInB->id, 'name' => 'Belongs to B']);
    }
}
