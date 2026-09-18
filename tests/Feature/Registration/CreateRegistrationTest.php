<?php

namespace Tests\Feature\Registration;

use App\Enums\OrganizationRole;
use App\Enums\RegistrationStatus;
use App\Enums\Status;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Organization $organization;

    protected Event $event;

    protected EventCategory $category;

    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->user, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $this->event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $this->category = EventCategory::factory()->create(['event_id' => $this->event->id]);
        $this->team = Team::factory()->create(['organization_id' => $this->organization->id]);
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'team_id' => $this->team->id,
            'event_category_id' => $this->category->id,
        ], $overrides);
    }

    public function test_owner_can_create_registration_as_draft(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('dashboard.events.registrations.store', $this->event), $this->payload());

        $registration = Registration::firstOrFail();
        $response->assertRedirect(route('dashboard.events.registrations.show', [$this->event, $registration]));

        $this->assertSame(RegistrationStatus::Draft, $registration->status);
        $this->assertSame($this->user->id, $registration->submitted_by);
        $this->assertNull($registration->submitted_at);
        $this->assertStringStartsWith('reg_', $registration->public_id);
    }

    public function test_team_and_category_are_required(): void
    {
        $this->actingAs($this->user)
            ->post(route('dashboard.events.registrations.store', $this->event), [])
            ->assertSessionHasErrors(['team_id', 'event_category_id']);
    }

    public function test_team_from_foreign_organization_is_rejected(): void
    {
        $foreignTeam = Team::factory()->create();

        $this->actingAs($this->user)
            ->post(route('dashboard.events.registrations.store', $this->event), $this->payload(['team_id' => $foreignTeam->id]))
            ->assertSessionHasErrors('team_id');

        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_category_must_belong_to_the_event(): void
    {
        $otherCategory = EventCategory::factory()->create(); // different event

        $this->actingAs($this->user)
            ->post(route('dashboard.events.registrations.store', $this->event), $this->payload(['event_category_id' => $otherCategory->id]))
            ->assertSessionHasErrors('event_category_id');
    }

    public function test_same_team_cannot_register_twice_to_the_same_category(): void
    {
        Registration::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
            'team_id' => $this->team->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('dashboard.events.registrations.store', $this->event), $this->payload())
            ->assertSessionHasErrors('team_id');

        $this->assertDatabaseCount('registrations', 1);
    }

    public function test_member_role_cannot_create_registration(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);

        $this->actingAs($member)
            ->post(route('dashboard.events.registrations.store', $this->event), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('dashboard.events.registrations.store', $this->event), $this->payload())
            ->assertRedirect(route('login'));
    }
}
