<?php

namespace Tests\Feature\Event;

use App\Enums\EventStaffRole;
use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventStaffTest extends TestCase
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

    protected function memberOfOrg(OrganizationRole $role = OrganizationRole::Member): User
    {
        $user = User::factory()->create();
        $this->organization->users()->attach($user, ['role' => $role, 'status' => Status::Active]);

        return $user;
    }

    public function test_owner_can_add_org_member_as_staff(): void
    {
        $staffUser = $this->memberOfOrg();

        $this->actingAs($this->owner)
            ->post(route('dashboard.events.staff.store', $this->event), [
                'user_id' => $staffUser->id_user,
                'role' => EventStaffRole::RegistrationStaff->value,
            ])
            ->assertRedirect(route('dashboard.events.show', $this->event));

        $this->assertDatabaseHas('event_staff', [
            'event_id' => $this->event->id,
            'user_id' => $staffUser->id_user,
            'role' => EventStaffRole::RegistrationStaff->value,
        ]);
    }

    public function test_cannot_add_non_org_member_as_staff(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($this->owner)
            ->post(route('dashboard.events.staff.store', $this->event), [
                'user_id' => $outsider->id_user,
                'role' => EventStaffRole::Viewer->value,
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('event_staff', ['user_id' => $outsider->id_user]);
    }

    public function test_same_user_cannot_be_staff_twice_on_one_event(): void
    {
        $staffUser = $this->memberOfOrg();
        $this->event->staff()->create(['user_id' => $staffUser->id_user, 'role' => EventStaffRole::Viewer]);

        $this->actingAs($this->owner)
            ->post(route('dashboard.events.staff.store', $this->event), [
                'user_id' => $staffUser->id_user,
                'role' => EventStaffRole::EventAdmin->value,
            ])
            ->assertSessionHasErrors('user_id');
    }

    public function test_owner_can_change_role_and_remove_staff(): void
    {
        $staffUser = $this->memberOfOrg();
        $staff = $this->event->staff()->create(['user_id' => $staffUser->id_user, 'role' => EventStaffRole::Viewer]);

        $this->actingAs($this->owner)->patch(route('dashboard.events.staff.update', [$this->event, $staff]), [
            'role' => EventStaffRole::ScoringAdmin->value,
        ]);
        $this->assertDatabaseHas('event_staff', ['id' => $staff->id, 'role' => EventStaffRole::ScoringAdmin->value]);

        $this->actingAs($this->owner)->delete(route('dashboard.events.staff.destroy', [$this->event, $staff]));
        $this->assertDatabaseMissing('event_staff', ['id' => $staff->id]);
    }

    public function test_member_role_cannot_manage_staff(): void
    {
        $member = $this->memberOfOrg(OrganizationRole::Member);
        $staffUser = $this->memberOfOrg();

        $this->actingAs($member)
            ->post(route('dashboard.events.staff.store', $this->event), [
                'user_id' => $staffUser->id_user,
                'role' => EventStaffRole::Viewer->value,
            ])
            ->assertForbidden();
    }

    public function test_staff_of_event_b_not_manipulable_via_event_a_url(): void
    {
        $eventB = Event::factory()->create(['organization_id' => $this->organization->id]);
        $staffUser = $this->memberOfOrg();
        $staffInB = $eventB->staff()->create(['user_id' => $staffUser->id_user, 'role' => EventStaffRole::Viewer]);

        $this->actingAs($this->owner)
            ->delete(route('dashboard.events.staff.destroy', [$this->event, $staffInB]))
            ->assertNotFound();

        $this->assertDatabaseHas('event_staff', ['id' => $staffInB->id]);
    }
}
