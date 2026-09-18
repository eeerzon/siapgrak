<?php

namespace Tests\Feature\Event;

use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Event}
     */
    protected function eventFor(OrganizationRole $role, EventStatus $status = EventStatus::Draft): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user, ['role' => $role, 'status' => Status::Active]);
        $event = Event::factory()->create(['organization_id' => $organization->id, 'status' => $status]);

        return [$user, $event];
    }

    public function test_draft_to_published_sets_published_at_once(): void
    {
        [$owner, $event] = $this->eventFor(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->patch(route('dashboard.events.status', $event), ['status' => 'published'])
            ->assertRedirect(route('dashboard.events.show', $event));

        $event->refresh();
        $this->assertSame(EventStatus::Published, $event->status);
        $this->assertNotNull($event->published_at);

        $firstPublishedAt = $event->published_at;

        // published -> draft -> published again: timestamp is not reset
        $this->actingAs($owner)->patch(route('dashboard.events.status', $event), ['status' => 'draft']);
        $this->actingAs($owner)->patch(route('dashboard.events.status', $event), ['status' => 'published']);

        $this->assertTrue($firstPublishedAt->equalTo($event->fresh()->published_at));
    }

    public function test_invalid_transition_is_rejected_and_status_unchanged(): void
    {
        [$owner, $event] = $this->eventFor(OrganizationRole::Owner);

        $this->actingAs($owner)
            ->patch(route('dashboard.events.status', $event), ['status' => 'finished'])
            ->assertSessionHasErrors('status');

        $this->assertSame(EventStatus::Draft, $event->fresh()->status);
    }

    public function test_finished_is_terminal(): void
    {
        [$owner, $event] = $this->eventFor(OrganizationRole::Owner, EventStatus::Finished);

        $this->actingAs($owner)
            ->patch(route('dashboard.events.status', $event), ['status' => 'ongoing'])
            ->assertSessionHasErrors('status');

        $this->assertSame(EventStatus::Finished, $event->fresh()->status);
    }

    public function test_cancel_is_allowed_from_ongoing(): void
    {
        [$owner, $event] = $this->eventFor(OrganizationRole::Owner, EventStatus::Ongoing);

        $this->actingAs($owner)->patch(route('dashboard.events.status', $event), ['status' => 'cancelled']);

        $this->assertSame(EventStatus::Cancelled, $event->fresh()->status);
    }

    public function test_member_role_cannot_change_status(): void
    {
        [$member, $event] = $this->eventFor(OrganizationRole::Member);

        $this->actingAs($member)
            ->patch(route('dashboard.events.status', $event), ['status' => 'published'])
            ->assertForbidden();

        $this->assertSame(EventStatus::Draft, $event->fresh()->status);
    }
}
