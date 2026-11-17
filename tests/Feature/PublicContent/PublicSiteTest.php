<?php

namespace Tests\Feature\PublicContent;

use App\Models\AgendaItem;
use App\Models\CommunityPost;
use App\Models\CompetitionListing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
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

    public function test_home_shows_all_three_sections_with_empty_states(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Info Lomba &amp; Kejuaraan', false)
            ->assertSee('Agenda &amp; Kegiatan', false)
            ->assertSee('Prestasi &amp; Kabar Komunitas', false)
            ->assertSee('Belum ada lomba yang dijadwalkan.');
    }

    public function test_home_shows_published_upcoming_content_only(): void
    {
        $this->listing(['title' => 'Lomba Tampil']);
        $this->listing(['title' => 'Lomba Draft', 'status' => 'draft']);
        $this->listing(['title' => 'Lomba Lewat', 'start_date' => today()->subWeek()]);
        AgendaItem::create(['title' => 'Agenda Tampil', 'start_at' => now()->addDay(), 'status' => 'published']);
        AgendaItem::create(['title' => 'Agenda Lewat', 'start_at' => now()->subDay(), 'status' => 'published']);
        CommunityPost::create(['type' => 'achievement', 'title' => 'Kabar Tampil', 'body' => 'Isi', 'status' => 'published']);
        CommunityPost::create(['type' => 'news', 'title' => 'Kabar Draft', 'body' => 'Isi', 'status' => 'draft']);

        $this->get('/')
            ->assertSee('Lomba Tampil')
            ->assertDontSee('Lomba Draft')
            ->assertDontSee('Lomba Lewat')
            ->assertSee('Agenda Tampil')
            ->assertDontSee('Agenda Lewat')
            ->assertSee('Kabar Tampil')
            ->assertDontSee('Kabar Draft');
    }

    public function test_multi_day_listing_stays_upcoming_until_it_ends(): void
    {
        $this->listing(['title' => 'Sedang Berjalan', 'start_date' => today()->subDay(), 'end_date' => today()->addDay()]);

        $this->get(route('site.competitions.index'))->assertSee('Sedang Berjalan');
        $this->get(route('site.competitions.index', ['arsip' => 1]))->assertDontSee('Sedang Berjalan');
    }

    public function test_past_listing_is_in_the_archive(): void
    {
        $this->listing(['title' => 'Lomba Lalu', 'start_date' => today()->subWeek()]);

        $this->get(route('site.competitions.index'))->assertDontSee('Lomba Lalu');
        $this->get(route('site.competitions.index', ['arsip' => 1]))->assertSee('Lomba Lalu');
    }

    public function test_draft_detail_pages_return_404(): void
    {
        $listing = $this->listing(['status' => 'draft']);
        $agenda = AgendaItem::create(['title' => 'Agenda', 'start_at' => now()->addDay(), 'status' => 'draft']);
        $post = CommunityPost::create(['type' => 'news', 'title' => 'Kabar', 'body' => 'Isi', 'status' => 'draft']);

        $this->get(route('site.competitions.show', $listing->slug))->assertNotFound();
        $this->get(route('site.agenda.show', $agenda->slug))->assertNotFound();
        $this->get(route('site.community.show', $post->slug))->assertNotFound();
    }

    public function test_managed_listing_links_to_kreenconnect_when_base_url_is_set(): void
    {
        config(['siapgrak.kreenconnect_event_base_url' => 'https://kreen.test/event/']);
        $listing = $this->listing(['is_managed' => true, 'kreen_slug' => 'lkbb-2026']);

        $this->get(route('site.competitions.show', $listing->slug))
            ->assertSee('https://kreen.test/event/lkbb-2026')
            ->assertSee('Daftar via Kreenconnect');
    }

    public function test_managed_listing_shows_no_transaction_link_without_base_url(): void
    {
        config(['siapgrak.kreenconnect_event_base_url' => null]);
        $listing = $this->listing(['is_managed' => true, 'kreen_slug' => 'lkbb-2026']);

        $this->get(route('site.competitions.show', $listing->slug))
            ->assertDontSee('Daftar via Kreenconnect')
            ->assertSee('Link pendaftaran belum tersedia.');
    }

    public function test_competition_list_filters_by_keyword_level_province_and_open_registration(): void
    {
        $this->listing(['title' => 'Piala Garuda', 'levels' => ['sma'], 'province' => 'DKI Jakarta', 'registration_deadline' => today()->addDay()]);
        $this->listing(['title' => 'Lomba Pelajar Bandung', 'levels' => ['smp', 'sma'], 'province' => 'Jawa Barat', 'registration_deadline' => today()->subDay()]);
        $this->listing(['title' => 'Festival SD', 'levels' => ['sd'], 'province' => 'Jawa Barat']);

        $this->get(route('site.competitions.index', ['q' => 'garuda']))
            ->assertSee('Piala Garuda')->assertDontSee('Lomba Pelajar Bandung')->assertDontSee('Festival SD');

        $this->get(route('site.competitions.index', ['jenjang' => 'sma']))
            ->assertSee('Piala Garuda')->assertSee('Lomba Pelajar Bandung')->assertDontSee('Festival SD');

        $this->get(route('site.competitions.index', ['provinsi' => 'Jawa Barat']))
            ->assertDontSee('Piala Garuda')->assertSee('Lomba Pelajar Bandung')->assertSee('Festival SD');

        // No deadline counts as open until the competition starts.
        $this->get(route('site.competitions.index', ['buka' => 1]))
            ->assertSee('Piala Garuda')->assertDontSee('Lomba Pelajar Bandung')->assertSee('Festival SD');
    }

    public function test_competition_list_ignores_unknown_filter_values(): void
    {
        $this->listing(['title' => 'Piala Garuda']);

        $this->get(route('site.competitions.index', ['jenjang' => 'kuliah', 'provinsi' => 'Atlantis']))
            ->assertOk()
            ->assertSee('Piala Garuda');
    }

    public function test_competition_card_shows_basic_info_and_detail_shows_everything(): void
    {
        $listing = $this->listing([
            'title' => 'Piala Garuda',
            'excerpt' => 'Ringkasan panjang lomba',
            'location' => 'Bandung',
            'province' => 'Jawa Barat',
            'levels' => ['smp', 'sma'],
            'organizer_name' => 'Purna Contoh',
        ]);

        $this->get(route('site.competitions.index'))
            ->assertSee(['Piala Garuda', 'Bandung, Jawa Barat', 'SMP/MTs', 'SMA/SMK/MA'])
            ->assertDontSee('Ringkasan panjang lomba');

        $this->get(route('site.competitions.show', $listing->slug))
            ->assertSee(['Ringkasan panjang lomba', 'Purna Contoh', 'SMP/MTs, SMA/SMK/MA']);
    }

    public function test_listing_without_registration_link_offers_contact_persons(): void
    {
        $listing = $this->listing(['contact_persons' => [['name' => 'Kak Rina', 'phone' => '0812-3456-7890']]]);

        $this->get(route('site.competitions.show', $listing->slug))
            ->assertSee('Daftar via Contact Person')
            ->assertSee('Kak Rina')
            ->assertSee('https://wa.me/6281234567890')
            ->assertDontSee('Link Pendaftaran');
    }

    public function test_listing_with_registration_link_shows_the_link(): void
    {
        $listing = $this->listing([
            'external_url' => 'https://penyelenggara.test/daftar',
            'contact_persons' => [['name' => 'Kak Rina', 'phone' => '+62 812 3456 7890']],
        ]);

        $this->get(route('site.competitions.show', $listing->slug))
            ->assertSee('https://penyelenggara.test/daftar')
            ->assertSee('Link Pendaftaran')
            ->assertDontSee('Daftar via Contact Person')
            ->assertSee('https://wa.me/6281234567890');
    }

    public function test_technical_meeting_shows_only_when_filled(): void
    {
        $withMeeting = $this->listing([
            'title' => 'Dengan TM',
            'technical_meeting_date' => '2026-11-28',
            'technical_meeting_location' => 'SMKN 21 Jakarta',
        ]);
        $withoutMeeting = $this->listing(['title' => 'Tanpa TM']);

        $this->get(route('site.competitions.show', $withMeeting->slug))
            ->assertSee('Technical Meeting')
            ->assertSee('28 November 2026 · SMKN 21 Jakarta');

        $this->get(route('site.competitions.show', $withoutMeeting->slug))
            ->assertDontSee('Technical Meeting');
    }

    public function test_closed_registration_hides_link_and_contact_persons(): void
    {
        $listing = $this->listing([
            'registration_deadline' => today()->subDay(),
            'external_url' => 'https://penyelenggara.test/daftar',
            'contact_persons' => [['name' => 'Kak Rina', 'phone' => '0812-3456-7890']],
        ]);

        $this->get(route('site.competitions.show', $listing->slug))
            ->assertSee('Pendaftaran sudah ditutup')
            ->assertDontSee('https://penyelenggara.test/daftar')
            ->assertDontSee('Kak Rina')
            ->assertDontSee('wa.me');
    }

    public function test_article_text_is_escaped(): void
    {
        $post = CommunityPost::create([
            'type' => 'news',
            'title' => 'Kabar',
            'body' => '<script>alert(1)</script>',
            'status' => 'published',
        ]);

        $this->get(route('site.community.show', $post->slug))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_community_list_filters_by_type(): void
    {
        CommunityPost::create(['type' => 'achievement', 'title' => 'Juara Umum', 'body' => 'Isi', 'status' => 'published']);
        CommunityPost::create(['type' => 'news', 'title' => 'Latihan Bersama', 'body' => 'Isi', 'status' => 'published']);

        $this->get(route('site.community.index', ['tipe' => 'achievement']))
            ->assertSee('Juara Umum')
            ->assertDontSee('Latihan Bersama');
    }

    public function test_agenda_and_community_can_be_searched(): void
    {
        AgendaItem::create(['title' => 'Latihan Gabungan Bandung', 'start_at' => now()->addDays(3), 'status' => 'published']);
        AgendaItem::create(['title' => 'Workshop Juri', 'location' => 'Yogyakarta', 'start_at' => now()->addDays(4), 'status' => 'published']);
        CommunityPost::create(['type' => 'news', 'title' => 'Tips Danton', 'body' => 'Isi tentang aba-aba', 'status' => 'published']);
        CommunityPost::create(['type' => 'news', 'title' => 'Kabar Lain', 'body' => 'Isi biasa', 'status' => 'published']);

        $this->get(route('site.agenda.index', ['q' => 'yogya']))
            ->assertOk()
            ->assertSee('Workshop Juri')
            ->assertDontSee('Latihan Gabungan Bandung');

        $this->get(route('site.community.index', ['q' => 'aba-aba']))
            ->assertOk()
            ->assertSee('Tips Danton')
            ->assertDontSee('Kabar Lain');
    }

    public function test_array_query_parameters_are_ignored_instead_of_crashing(): void
    {
        $this->listing(['title' => 'Lomba Tetap Tampil']);

        foreach (['/info-lomba?q[]=x', '/info-lomba?jenjang[]=sma', '/kabar?tipe[]=news', '/agenda?q[]=x', '/kabar?q[]=x'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/info-lomba?q[]=x')->assertSee('Lomba Tetap Tampil');
    }

    public function test_unknown_page_shows_the_indonesian_404_page(): void
    {
        $this->get('/info-lomba/tidak-ada')
            ->assertNotFound()
            ->assertSee('Halamannya tidak ketemu');
    }

    public function test_competition_list_shows_eight_per_page(): void
    {
        foreach (range(1, 9) as $i) {
            $this->listing(['title' => "Lomba Urut {$i}", 'start_date' => today()->addDays($i)]);
        }

        $this->get(route('site.competitions.index'))
            ->assertSee('Lomba Urut 8')
            ->assertDontSee('Lomba Urut 9');

        $this->get(route('site.competitions.index', ['page' => 2]))
            ->assertSee('Lomba Urut 9')
            ->assertDontSee('Lomba Urut 8');
    }

    public function test_agenda_list_loads_eight_at_a_time_with_a_next_page_url(): void
    {
        foreach (range(1, 9) as $i) {
            AgendaItem::create(['title' => "Agenda Urut {$i}", 'start_at' => now()->addDays($i), 'status' => 'published']);
        }

        $this->get(route('site.agenda.index'))
            ->assertSee('Agenda Urut 8')
            ->assertDontSee('Agenda Urut 9')
            ->assertSee('data-next="'.route('site.agenda.index', ['page' => 2]).'"', false)
            ->assertDontSee('Berikutnya')
            ->assertDontSee('rel="next"', false);

        $this->get(route('site.agenda.index', ['page' => 2]))
            ->assertSee('Agenda Urut 9')
            ->assertSee('data-next=""', false);
    }

    public function test_agenda_detail_shows_poster_and_details(): void
    {
        $agendaItem = AgendaItem::create([
            'title' => 'Workshop Juri',
            'location' => 'Aula Contoh',
            'cover_path' => 'content/poster.jpg',
            'start_at' => now()->addDays(3)->setTime(9, 0),
            'status' => 'published',
        ]);

        $this->get(route('site.agenda.show', $agendaItem->slug))
            ->assertOk()
            ->assertSee('storage/content/poster.jpg', false)
            ->assertSee('aspect-[4/5]', false)
            ->assertSee('Aula Contoh')
            ->assertSee('Akan datang');
    }
}
