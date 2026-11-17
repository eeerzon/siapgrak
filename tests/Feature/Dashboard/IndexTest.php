<?php

namespace Tests\Feature\Dashboard;

use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
    }

    public function test_summary_counts_reflect_only_own_organization_data(): void
    {
        $this->markTestSkipped('Dashboard organisasi lama dinonaktifkan (2026-11-10), lihat DashboardController.');

        Event::factory()->create(['organization_id' => $this->organization->id, 'status' => EventStatus::RegistrationOpen]);
        Event::factory()->create(['organization_id' => $this->organization->id, 'status' => EventStatus::Draft]);
        Event::factory()->create(['organization_id' => $this->organization->id, 'status' => EventStatus::Cancelled]);
        Team::factory()->create(['organization_id' => $this->organization->id]);
        Team::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.index'));

        $response->assertOk();
        // Only RegistrationOpen counts as "active" — Draft and Cancelled are excluded.
        $this->assertSame(1, $response->viewData('activeEventsCount'));
        $this->assertSame(2, $response->viewData('teamsCount'));
    }

    public function test_upcoming_event_shows_the_nearest_one_only(): void
    {
        $this->markTestSkipped('Dashboard organisasi lama dinonaktifkan (2026-11-10), lihat DashboardController.');

        $far = Event::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => EventStatus::RegistrationOpen,
            'start_date' => now()->addMonths(2),
            'end_date' => now()->addMonths(2),
        ]);
        $near = Event::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => EventStatus::RegistrationOpen,
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(3),
        ]);
        Event::factory()->create([
            'organization_id' => $this->organization->id,
            'status' => EventStatus::Finished,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(10),
        ]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.index'));

        $this->assertSame(2, $response->viewData('upcomingCount'));
        $this->assertTrue($response->viewData('upcomingEvent')->is($near));
        $response->assertSee($near->name);
        $response->assertDontSee($far->name);
    }

    public function test_participants_count_sums_registrations_across_own_events(): void
    {
        $this->markTestSkipped('Dashboard organisasi lama dinonaktifkan (2026-11-10), lihat DashboardController.');

        $event = Event::factory()->create(['organization_id' => $this->organization->id]);
        Registration::factory()->count(3)->create(['event_id' => $event->id]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.index'));

        $this->assertSame(3, $response->viewData('participantsCount'));
    }

    public function test_recent_activity_lists_own_organizations_entries(): void
    {
        $this->markTestSkipped('Dashboard organisasi lama dinonaktifkan (2026-11-10), lihat DashboardController.');

        ActivityLog::factory()->create([
            'user_id' => $this->owner->id_user,
            'organization_id' => $this->organization->id,
            'action' => 'registration_approved',
        ]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.index'));

        $response->assertSee('Registrasi disetujui');
    }

    public function test_tenant_isolation_data_from_another_organization_never_appears(): void
    {
        $this->markTestSkipped('Dashboard organisasi lama dinonaktifkan (2026-11-10), lihat DashboardController.');

        $outsiderOrg = Organization::factory()->create();
        $outsiderEvent = Event::factory()->create([
            'organization_id' => $outsiderOrg->id,
            'status' => EventStatus::RegistrationOpen,
            'start_date' => now()->addDays(1),
        ]);
        Team::factory()->create(['organization_id' => $outsiderOrg->id]);
        Registration::factory()->create(['event_id' => $outsiderEvent->id]);
        ActivityLog::factory()->create([
            'organization_id' => $outsiderOrg->id,
            'action' => 'award_created',
        ]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.index'));

        $response->assertOk();
        $this->assertSame(0, $response->viewData('activeEventsCount'));
        $this->assertSame(0, $response->viewData('teamsCount'));
        $this->assertSame(0, $response->viewData('participantsCount'));
        $this->assertSame(0, $response->viewData('upcomingCount'));
        $this->assertNull($response->viewData('upcomingEvent'));
        $this->assertTrue($response->viewData('recentActivity')->isEmpty());
        $response->assertDontSee($outsiderEvent->name);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard.index'))->assertRedirect(route('login'));
    }
}
