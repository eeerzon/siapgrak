<?php

namespace Tests\Feature\PublicContent;

use App\Models\AgendaItem;
use App\Models\CommunityPost;
use App\Models\CompetitionListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function listing(array $overrides = []): CompetitionListing
    {
        return CompetitionListing::create($overrides + [
            'title' => 'LKBB Nasional',
            'start_date' => today()->addWeek(),
            'status' => 'published',
        ]);
    }

    public function test_percent_and_underscore_in_search_are_literal_characters(): void
    {
        $this->listing(['title' => 'Lomba Diskon 50% Pendaftaran']);
        $this->listing(['title' => 'Lomba Biasa']);
        AgendaItem::create(['title' => 'Agenda Biasa', 'start_at' => now()->addDay(), 'status' => 'published']);
        CommunityPost::create(['type' => 'news', 'title' => 'Kabar Biasa', 'body' => 'Isi', 'status' => 'published']);

        $this->get('/info-lomba?q=%25')->assertSee('Lomba Diskon 50% Pendaftaran')->assertDontSee('Lomba Biasa');
        $this->get('/info-lomba?q=_')->assertDontSee('Lomba Biasa')->assertDontSee('Lomba Diskon');
        $this->get('/agenda?q=%25')->assertDontSee('Agenda Biasa');
        $this->get('/kabar?q=_')->assertDontSee('Kabar Biasa');
        $this->get('/info-lomba?q=biasa')->assertSee('Lomba Biasa');
    }

    public function test_page_past_the_end_redirects_to_the_last_page(): void
    {
        foreach (range(1, 9) as $n) {
            $this->listing(['title' => "Lomba {$n}", 'start_date' => today()->addDays($n)]);
        }

        $this->get('/info-lomba?page=50')->assertRedirect('/info-lomba?page=2');
        $this->get('/info-lomba?q=Lomba&page=50')->assertRedirect('/info-lomba?q=Lomba&page=2');
        $this->get('/info-lomba?page=2')->assertOk()->assertSee('Lomba 9');
        $this->get('/kabar?page=5')->assertOk();
    }

    public function test_province_list_only_has_provinces_with_a_competition_on_the_current_tab(): void
    {
        $this->listing(['title' => 'Lomba Bandung', 'province' => 'Jawa Barat']);
        $this->listing(['title' => 'Lomba Lama Bali', 'province' => 'Bali', 'start_date' => today()->subMonth()]);

        $this->get('/info-lomba')->assertSee('Jawa Barat')->assertDontSee('Bali');
        $this->get('/info-lomba?arsip=1')->assertSee('Bali')->assertDontSee('Jawa Barat');
        // A chosen province stays selectable even when it has nothing on this tab.
        $this->get('/info-lomba?provinsi=Bali')->assertSee('Bali');
    }

    public function test_registration_deadline_cannot_be_after_the_competition(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $input = ['title' => 'LKBB Nasional', 'start_date' => '2030-01-10', 'is_managed' => '0', 'status' => 'draft'];

        $this->actingAs($admin)->post(route('dashboard.content.competitions.store'), $input + ['registration_deadline' => '2030-01-11'])
            ->assertSessionHasErrors(['registration_deadline' => 'Batas pendaftaran tidak boleh setelah lomba selesai.']);

        // Up to the last day of a multi-day competition is fine.
        $this->actingAs($admin)->post(route('dashboard.content.competitions.store'), $input + ['end_date' => '2030-01-12', 'registration_deadline' => '2030-01-12'])
            ->assertSessionHasNoErrors();
    }

    public function test_dashboard_dates_use_indonesian_month_names(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $year = today()->year + 1;
        $this->listing(['title' => 'Lomba Desember', 'start_date' => "{$year}-12-20", 'registration_deadline' => "{$year}-12-01", 'id_user' => $admin->id_user]);

        $this->actingAs($admin)->get(route('dashboard.index'))->assertSee('1 Des')->assertDontSee('1 Dec');
    }

    public function test_results_page_links_back_to_the_competition(): void
    {
        $listing = $this->listing(['title' => 'Lomba Hasil']);
        $listing->categories()->create(['name' => 'SMA'])->rounds()->create(['name' => 'Final']);

        $this->get(route('site.competitions.results', $listing->slug))
            ->assertOk()
            ->assertSee('href="'.route('site.competitions.show', $listing->slug).'"', false);
    }
}
