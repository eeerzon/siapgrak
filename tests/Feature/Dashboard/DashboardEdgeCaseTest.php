<?php

namespace Tests\Feature\Dashboard;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\EventStaff;
use App\Models\Judge;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): array
    {
        $owner = User::factory()->create(['name' => 'Pemilik Organisasi']);
        $organization = Organization::factory()->create();
        $organization->users()->attach($owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        return [$owner, $organization];
    }

    public function test_user_relations_resolve_through_the_user_id_column(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(EventStaff::factory()->create(['user_id' => $user->id_user])->user->is($user));
        $this->assertTrue(ActivityLog::factory()->create(['user_id' => $user->id_user])->user->is($user));
        $this->assertTrue(Judge::factory()->create(['user_id' => $user->id_user])->user->is($user));
        $this->assertTrue(TeamMember::factory()->create(['user_id' => $user->id_user])->user->is($user));

        [, $organization] = $this->owner();
        $organization->users()->attach($user, ['role' => OrganizationRole::Member, 'status' => Status::Active]);
        $this->assertTrue(OrganizationUser::where('user_id', $user->id_user)->first()->user->is($user));
    }

    public function test_organization_members_page_lists_member_names(): void
    {
        [$owner, $organization] = $this->owner();

        $this->actingAs($owner)->get(route('dashboard.organizations.members.index', $organization))
            ->assertOk()
            ->assertSee('Pemilik Organisasi')
            ->assertSee($owner->email);
    }

    public function test_event_staff_shows_the_user_name(): void
    {
        [$owner, $organization] = $this->owner();
        $event = Event::factory()->create(['organization_id' => $organization->id]);
        EventStaff::factory()->create(['event_id' => $event->id, 'user_id' => User::factory()->create(['name' => 'Staf Lapangan'])->id_user]);

        $this->actingAs($owner)->get(route('dashboard.events.show', $event))->assertOk()->assertSee('Staf Lapangan');
    }

    public function test_array_filters_do_not_crash_list_pages(): void
    {
        [$owner, $organization] = $this->owner();
        $event = Event::factory()->create(['organization_id' => $organization->id]);
        $this->actingAs($owner);

        foreach (['dashboard.events.index', 'dashboard.teams.index'] as $name) {
            $this->get(route($name, ['status' => ['a']]))->assertOk();
            $this->get(route($name, ['search' => ['a']]))->assertOk();
        }
        $this->get(route('dashboard.events.registrations.index', [$event, 'status' => ['a']]))->assertOk();

        $admin = User::factory()->create(['is_platform_admin' => true]);
        $this->actingAs($admin)->get(route('dashboard.judges.index', ['status' => ['a'], 'search' => ['a']]))->assertOk();
    }

    public function test_percent_in_dashboard_search_is_literal(): void
    {
        [$owner, $organization] = $this->owner();
        Team::factory()->create(['organization_id' => $organization->id, 'name' => 'Paskibra 100% Juara']);
        Team::factory()->create(['organization_id' => $organization->id, 'name' => 'Paskibra Biasa']);

        $this->actingAs($owner)->get(route('dashboard.teams.index', ['search' => '%']))
            ->assertSee('Paskibra 100% Juara')
            ->assertDontSee('Paskibra Biasa');
    }
}
