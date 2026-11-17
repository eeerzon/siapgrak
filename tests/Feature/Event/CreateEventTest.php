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

class CreateEventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Organization}
     */
    protected function userInOrg(OrganizationRole $role = OrganizationRole::Owner): array
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user, ['role' => $role, 'status' => Status::Active]);

        return [$user, $organization];
    }

    /**
     * @return array<string, string>
     */
    protected function validPayload(Organization $organization, array $overrides = []): array
    {
        return array_merge([
            'organization_id' => $organization->id,
            'name' => 'LKBB Garuda 2026',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-12',
        ], $overrides);
    }

    public function test_active_member_can_create_event_in_own_organization(): void
    {
        [$user, $organization] = $this->userInOrg(OrganizationRole::Member);

        $response = $this->actingAs($user)->post(route('dashboard.events.store'), $this->validPayload($organization));

        $event = Event::where('name', 'LKBB Garuda 2026')->firstOrFail();
        $response->assertRedirect(route('dashboard.events.show', $event));

        $this->assertSame($organization->id, $event->organization_id);
        $this->assertSame($user->id_user, $event->created_by);
        $this->assertSame(EventStatus::Draft, $event->status);
        $this->assertNull($event->published_at);
        $this->assertNotEmpty($event->slug);
    }

    public function test_name_start_and_end_dates_are_required(): void
    {
        [$user, $organization] = $this->userInOrg();

        $this->actingAs($user)
            ->post(route('dashboard.events.store'), ['organization_id' => $organization->id])
            ->assertSessionHasErrors(['name', 'start_date', 'end_date']);
    }

    public function test_end_date_cannot_precede_start_date(): void
    {
        [$user, $organization] = $this->userInOrg();

        $this->actingAs($user)
            ->post(route('dashboard.events.store'), $this->validPayload($organization, [
                'start_date' => '2026-10-12',
                'end_date' => '2026-10-10',
            ]))
            ->assertSessionHasErrors('end_date');

        $this->assertDatabaseMissing('events', ['name' => 'LKBB Garuda 2026']);
    }

    public function test_organization_id_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('dashboard.events.store'), ['name' => 'Orphan Event', 'start_date' => '2026-10-12', 'end_date' => '2026-10-12'])
            ->assertSessionHasErrors('organization_id');

        $this->assertDatabaseMissing('events', ['name' => 'Orphan Event']);
    }

    public function test_user_cannot_attach_event_to_foreign_organization(): void
    {
        [$user] = $this->userInOrg();
        $foreign = Organization::factory()->create();

        $this->actingAs($user)
            ->post(route('dashboard.events.store'), $this->validPayload($foreign, ['name' => 'Hijacked Event']))
            ->assertSessionHasErrors('organization_id');

        $this->assertDatabaseMissing('events', ['name' => 'Hijacked Event']);
    }

    public function test_inactive_membership_cannot_create_event(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $organization->users()->attach($user, ['role' => OrganizationRole::Owner, 'status' => Status::Inactive]);

        $this->actingAs($user)
            ->post(route('dashboard.events.store'), $this->validPayload($organization, ['name' => 'Inactive Member Event']))
            ->assertSessionHasErrors('organization_id');

        $this->assertDatabaseMissing('events', ['name' => 'Inactive Member Event']);
    }

    public function test_slug_is_unique(): void
    {
        [$user, $organization] = $this->userInOrg();
        Event::factory()->create(['slug' => 'dup-event-slug']);

        $this->actingAs($user)
            ->post(route('dashboard.events.store'), $this->validPayload($organization, ['slug' => 'dup-event-slug']))
            ->assertSessionHasErrors('slug');
    }

    public function test_guest_cannot_create_event(): void
    {
        $this->post(route('dashboard.events.store'), ['name' => 'Guest Event'])
            ->assertRedirect(route('login'));
    }
}
