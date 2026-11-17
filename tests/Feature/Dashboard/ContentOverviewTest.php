<?php

namespace Tests\Feature\Dashboard;

use App\Models\AgendaItem;
use App\Models\CompetitionListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_dashboard_summarizes_only_their_own_content(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $otherAdmin = User::factory()->create(['is_platform_admin' => true]);

        CompetitionListing::create(['title' => 'Lomba Buka Saya', 'start_date' => today()->addDays(20), 'registration_deadline' => today()->addDays(10), 'status' => 'published', 'id_user' => $admin->id_user]);
        CompetitionListing::create(['title' => 'Lomba Tutup Saya', 'start_date' => today()->addDays(20), 'registration_deadline' => today()->subDay(), 'status' => 'published', 'id_user' => $admin->id_user]);
        CompetitionListing::create(['title' => 'Lomba Draft Saya', 'start_date' => today()->addDays(30), 'status' => 'draft', 'id_user' => $admin->id_user]);
        CompetitionListing::create(['title' => 'Lomba Lewat Saya', 'start_date' => today()->subDays(30), 'status' => 'published', 'id_user' => $admin->id_user]);
        CompetitionListing::create(['title' => 'Lomba Admin Lain', 'start_date' => today()->addDays(5), 'status' => 'published', 'id_user' => $otherAdmin->id_user]);
        AgendaItem::create(['title' => 'Agenda Saya', 'start_at' => now()->addDays(3), 'status' => 'draft', 'id_user' => $admin->id_user]);
        AgendaItem::create(['title' => 'Agenda Admin Lain', 'start_at' => now()->addDays(3), 'status' => 'draft', 'id_user' => $otherAdmin->id_user]);

        $response = $this->actingAs($admin)->get(route('dashboard.index'));

        $response->assertOk()->assertViewIs('dashboard.content-overview');
        $response->assertViewHas('stats', [
            'published' => 3,
            'draft' => 1,
            'registrationOpen' => 1,
            'upcomingAgenda' => 1,
            'communityPosts' => 0,
        ]);
        $response->assertSeeInOrder(['Lomba Buka Saya', 'Lomba Tutup Saya', 'Lomba Draft Saya']);
        $response->assertSee('Agenda Saya');
        $response->assertDontSee('Lomba Lewat Saya');
        $response->assertDontSee('Lomba Admin Lain');
        $response->assertDontSee('Agenda Admin Lain');
    }

    public function test_every_user_gets_the_content_dashboard_with_only_their_own_data(): void
    {
        $organizer = User::factory()->create();
        $admin = User::factory()->create(['is_platform_admin' => true]);
        CompetitionListing::create(['title' => 'Lomba Milik Admin', 'start_date' => today()->addDays(5), 'status' => 'published', 'id_user' => $admin->id_user]);

        $this->actingAs($organizer)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertViewIs('dashboard.content-overview')
            ->assertViewHas('stats', fn (array $stats) => array_sum($stats) === 0)
            ->assertDontSee('Lomba Milik Admin');
    }

    public function test_events_menu_opens_content_and_teams_is_hidden(): void
    {
        $response = $this->actingAs(User::factory()->create(['is_platform_admin' => true]))
            ->get(route('dashboard.index'));

        $response->assertSee('href="'.route('dashboard.content.competitions.index').'"', false);
        $response->assertDontSee('href="'.route('dashboard.events.index').'"', false);
        $response->assertDontSee('href="'.route('dashboard.teams.index').'"', false);
        $response->assertDontSee('>Konten<', false);
    }
}
