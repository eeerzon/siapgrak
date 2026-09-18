<?php

namespace Tests\Feature\Event;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateEventTest extends TestCase
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

    /**
     * @return array<string, string>
     */
    protected function payload(Event $event, array $overrides = []): array
    {
        return array_merge([
            'name' => $event->name,
            'slug' => $event->slug,
            'start_date' => $event->start_date->toDateString(),
            'end_date' => $event->end_date->toDateString(),
        ], $overrides);
    }

    public function test_owner_can_update_event(): void
    {
        [$owner, $event] = $this->eventFor(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->put(route('dashboard.events.update', $event), $this->payload($event, ['name' => 'Renamed Event']))
            ->assertRedirect(route('dashboard.events.show', $event));

        $this->assertDatabaseHas('events', ['id' => $event->id, 'name' => 'Renamed Event']);
    }

    public function test_member_role_cannot_update_event(): void
    {
        [$member, $event] = $this->eventFor(OrganizationRole::Member);

        $this->actingAs($member)
            ->put(route('dashboard.events.update', $event), $this->payload($event, ['name' => 'Should Not Save']))
            ->assertForbidden();

        $this->assertDatabaseMissing('events', ['name' => 'Should Not Save']);
    }

    public function test_organization_id_cannot_be_changed_via_update(): void
    {
        [$owner, $event] = $this->eventFor(OrganizationRole::Owner);
        $otherOrg = Organization::factory()->create();

        $this->actingAs($owner)->put(route('dashboard.events.update', $event), $this->payload($event, [
            'organization_id' => $otherOrg->id,
        ]));

        $this->assertSame($event->organization_id, $event->fresh()->organization_id);
    }

    public function test_slug_must_stay_unique_but_may_keep_its_own(): void
    {
        [$owner, $event] = $this->eventFor(OrganizationRole::Owner);
        $other = Event::factory()->create(['slug' => 'taken-slug']);

        $this->actingAs($owner)
            ->put(route('dashboard.events.update', $event), $this->payload($event, ['slug' => 'taken-slug']))
            ->assertSessionHasErrors('slug');

        // keeping its own slug is fine
        $this->actingAs($owner)
            ->put(route('dashboard.events.update', $event), $this->payload($event))
            ->assertSessionHasNoErrors();
    }
}
