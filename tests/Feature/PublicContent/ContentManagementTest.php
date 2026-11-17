<?php

namespace Tests\Feature\PublicContent;

use App\Models\AgendaItem;
use App\Models\CommunityPost;
use App\Models\CompetitionListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_platform_admin' => true]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function listingInput(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'LKBB Nasional',
            'start_date' => '2030-01-10',
            'is_managed' => '0',
            'status' => 'draft',
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function contentIndexRoutes(): array
    {
        return [
            'competitions' => ['dashboard.content.competitions.index'],
            'agenda' => ['dashboard.content.agenda.index'],
            'community' => ['dashboard.content.community.index'],
        ];
    }

    #[DataProvider('contentIndexRoutes')]
    public function test_guest_is_redirected_to_login(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('login'));
    }

    #[DataProvider('contentIndexRoutes')]
    public function test_organizer_without_platform_role_is_forbidden(string $route): void
    {
        $this->actingAs(User::factory()->create())->get(route($route))->assertForbidden();
    }

    #[DataProvider('contentIndexRoutes')]
    public function test_platform_admin_can_open_index(string $route): void
    {
        $this->actingAs($this->admin())->get(route($route))->assertOk();
    }

    public function test_organizer_cannot_write_content(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('dashboard.content.competitions.store'), $this->listingInput())
            ->assertForbidden();

        $this->assertDatabaseCount('competition_listings', 0);
    }

    public function test_registration_cannot_grant_platform_admin(): void
    {
        $this->post(route('register'), [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_platform_admin' => '1',
        ]);

        $this->assertFalse((bool) User::where('email', 'penyusup@example.com')->value('is_platform_admin'));
    }

    public function test_events_menu_links_to_content_for_every_user(): void
    {
        // Since 2026-11-10 the "Events" menu opens the content pages for all users;
        // only platform admins can open them (organizers get 403, see above).
        $url = route('dashboard.content.competitions.index');

        $this->actingAs(User::factory()->create())->get(route('dashboard.teams.index'))->assertSee($url);
        $this->actingAs($this->admin())->get(route('dashboard.teams.index'))->assertSee($url);
    }

    public function test_managed_listing_requires_kreen_slug(): void
    {
        $this->actingAs($this->admin())
            ->post(route('dashboard.content.competitions.store'), $this->listingInput(['is_managed' => '1']))
            ->assertSessionHasErrors('kreen_slug');
    }

    public function test_kreen_slug_must_be_unique_but_ignores_its_own_record(): void
    {
        $admin = $this->admin();
        $existing = CompetitionListing::create([
            'title' => 'Lama', 'start_date' => '2030-01-01', 'status' => 'draft',
            'is_managed' => true, 'kreen_slug' => 'lkbb-2030', 'id_user' => $admin->id_user,
        ]);

        $this->actingAs($admin)
            ->post(route('dashboard.content.competitions.store'), $this->listingInput(['is_managed' => '1', 'kreen_slug' => 'lkbb-2030']))
            ->assertSessionHasErrors('kreen_slug');

        $this->actingAs($admin)
            ->put(route('dashboard.content.competitions.update', $existing), $this->listingInput(['is_managed' => '1', 'kreen_slug' => 'lkbb-2030']))
            ->assertSessionHasNoErrors();
    }

    public function test_external_url_rejects_non_http_schemes(): void
    {
        $this->actingAs($this->admin())
            ->post(route('dashboard.content.competitions.store'), $this->listingInput(['external_url' => 'javascript:alert(1)']))
            ->assertSessionHasErrors('external_url');
    }

    public function test_switching_listing_type_clears_the_unused_field(): void
    {
        $admin = $this->admin();
        $listing = CompetitionListing::create([
            'title' => 'Lama', 'start_date' => '2030-01-01', 'status' => 'draft',
            'is_managed' => true, 'kreen_slug' => 'lkbb-2030', 'id_user' => $admin->id_user,
        ]);

        $this->actingAs($admin)->put(route('dashboard.content.competitions.update', $listing), $this->listingInput([
            'kreen_slug' => 'isian-lama-di-form-tersembunyi',
            'external_url' => 'https://penyelenggara.test',
        ]))->assertSessionHasNoErrors();

        $listing->refresh();
        $this->assertFalse($listing->is_managed);
        $this->assertNull($listing->kreen_slug);
        $this->assertSame('https://penyelenggara.test', $listing->external_url);

        $this->actingAs($admin)->put(route('dashboard.content.competitions.update', $listing), $this->listingInput([
            'is_managed' => '1',
            'kreen_slug' => 'lkbb-2030',
            'external_url' => 'https://penyelenggara.test',
        ]))->assertSessionHasNoErrors();

        $listing->refresh();
        $this->assertSame('lkbb-2030', $listing->kreen_slug);
        $this->assertNull($listing->external_url);
    }

    public function test_slug_is_unique_and_stays_stable_when_title_changes(): void
    {
        $first = CompetitionListing::create(['title' => 'LKBB Nasional', 'start_date' => '2030-01-01', 'status' => 'draft']);
        $second = CompetitionListing::create(['title' => 'LKBB Nasional', 'start_date' => '2030-01-01', 'status' => 'draft']);

        $this->assertSame('lkbb-nasional', $first->slug);
        $this->assertSame('lkbb-nasional-2', $second->slug);

        $first->update(['title' => 'Judul Baru']);
        $this->assertSame('lkbb-nasional', $first->fresh()->slug);
    }

    public function test_published_at_is_set_once_on_first_publish(): void
    {
        $post = CommunityPost::create(['type' => 'news', 'title' => 'Kabar', 'body' => 'Isi', 'status' => 'draft']);
        $this->assertNull($post->published_at);

        $post->update(['status' => 'published']);
        $publishedAt = $post->fresh()->published_at;
        $this->assertNotNull($publishedAt);

        $this->travel(1)->day();
        $post->fresh()->update(['title' => 'Kabar Revisi']);
        $this->assertTrue($publishedAt->equalTo($post->fresh()->published_at));
    }

    public function test_cover_is_stored_replaced_and_deleted(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('dashboard.content.community.store'), [
            'type' => 'achievement', 'title' => 'Juara', 'body' => 'Isi', 'status' => 'published',
            'cover' => UploadedFile::fake()->image('a.jpg'),
        ])->assertSessionHasNoErrors();

        $post = CommunityPost::firstOrFail();
        $firstPath = $post->cover_path;
        Storage::disk('public')->assertExists($firstPath);

        $this->actingAs($admin)->put(route('dashboard.content.community.update', $post), [
            'type' => 'achievement', 'title' => 'Juara', 'body' => 'Isi', 'status' => 'published',
            'cover' => UploadedFile::fake()->image('b.png'),
        ])->assertSessionHasNoErrors();

        $secondPath = $post->fresh()->cover_path;
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        $this->actingAs($admin)->delete(route('dashboard.content.community.destroy', $post));
        Storage::disk('public')->assertMissing($secondPath);
        $this->assertDatabaseCount('community_posts', 0);
    }

    public function test_cover_rejects_svg(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('dashboard.content.community.store'), [
            'type' => 'news', 'title' => 'Kabar', 'body' => 'Isi', 'status' => 'draft',
            'cover' => UploadedFile::fake()->create('a.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors('cover');
    }

    public function test_agenda_end_cannot_precede_start(): void
    {
        $this->actingAs($this->admin())->post(route('dashboard.content.agenda.store'), [
            'title' => 'Agenda', 'start_at' => '2030-01-10T10:00', 'end_at' => '2030-01-10T09:00', 'status' => 'draft',
        ])->assertSessionHasErrors('end_at');
    }

    public function test_admin_can_run_the_full_crud_on_agenda_and_edit_pages_render(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('dashboard.content.agenda.store'), [
            'title' => 'Rapat Pelatih', 'start_at' => '2030-01-10T10:00', 'status' => 'published',
        ])->assertRedirect(route('dashboard.content.agenda.index'));

        $item = AgendaItem::firstOrFail();
        $this->assertSame($admin->id_user, $item->id_user);

        $this->actingAs($admin)->get(route('dashboard.content.agenda.edit', $item))
            ->assertOk()
            ->assertSee('2030-01-10T10:00');

        $this->actingAs($admin)->delete(route('dashboard.content.agenda.destroy', $item));
        $this->assertDatabaseCount('agenda_items', 0);
    }

    public function test_create_and_edit_forms_render(): void
    {
        $admin = $this->admin();
        $listing = CompetitionListing::create(['title' => 'Lomba', 'start_date' => '2030-01-01', 'status' => 'draft', 'id_user' => $admin->id_user]);
        $post = CommunityPost::create(['type' => 'news', 'title' => 'Kabar', 'body' => 'Isi', 'status' => 'draft', 'id_user' => $admin->id_user]);

        foreach ([
            route('dashboard.content.competitions.create'),
            route('dashboard.content.competitions.edit', $listing),
            route('dashboard.content.agenda.create'),
            route('dashboard.content.community.create'),
            route('dashboard.content.community.edit', $post),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_listing_details_are_saved_and_blank_contact_rows_dropped(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('dashboard.content.competitions.store'), $this->listingInput([
            'province' => 'Jawa Barat',
            'levels' => ['smp', 'sma'],
            'registration_deadline' => '2030-01-05',
            'technical_meeting_date' => '2030-01-07',
            'technical_meeting_location' => 'Daring',
            'contact_persons' => [
                ['name' => 'Kak Rina', 'phone' => '0812-3456-7890'],
                ['name' => '', 'phone' => ''],
                ['name' => 'Kak Budi', 'phone' => '+62 813 0000 1111'],
            ],
        ]))->assertSessionHasNoErrors();

        $listing = CompetitionListing::firstOrFail();
        $this->assertSame('Jawa Barat', $listing->province);
        $this->assertSame(['smp', 'sma'], $listing->levels);
        $this->assertSame('2030-01-05', $listing->registration_deadline->toDateString());
        $this->assertSame('2030-01-07', $listing->technical_meeting_date->toDateString());
        $this->assertSame('Daring', $listing->technical_meeting_location);
        $this->assertSame([
            ['name' => 'Kak Rina', 'phone' => '0812-3456-7890'],
            ['name' => 'Kak Budi', 'phone' => '+62 813 0000 1111'],
        ], $listing->contact_persons);

        // Unchecking every level and clearing every contact empties both fields.
        $this->actingAs($admin)->put(route('dashboard.content.competitions.update', $listing), $this->listingInput([
            'contact_persons' => [['name' => '', 'phone' => '']],
        ]))->assertSessionHasNoErrors();

        $listing->refresh();
        $this->assertNull($listing->levels);
        $this->assertNull($listing->contact_persons);
    }

    public function test_listing_details_are_validated(): void
    {
        $this->actingAs($this->admin())->post(route('dashboard.content.competitions.store'), $this->listingInput([
            'province' => 'Atlantis',
            'levels' => ['kuliah'],
            'contact_persons' => [['name' => 'Kak Rina', 'phone' => ''], ['name' => '', 'phone' => '0812']],
        ]))->assertSessionHasErrors([
            'province', 'levels.0', 'contact_persons.0.phone', 'contact_persons.1.name', 'contact_persons.1.phone',
        ]);
    }

    public function test_competition_poster_must_be_four_by_five(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('dashboard.content.competitions.store'), $this->listingInput([
            'cover' => UploadedFile::fake()->image('landscape.jpg', 1200, 675),
        ]))->assertSessionHasErrors('cover');

        $this->actingAs($admin)->post(route('dashboard.content.competitions.store'), $this->listingInput([
            'cover' => UploadedFile::fake()->image('feed.jpg', 1080, 1350),
        ]))->assertSessionHasNoErrors();
    }

    public function test_admin_can_open_competition_detail_from_the_list(): void
    {
        $admin = $this->admin();
        $listing = CompetitionListing::create([
            'title' => 'LKBB Nasional', 'start_date' => '2030-01-01', 'status' => 'draft',
            'location' => 'Bandung', 'organizer_name' => 'Purna Paskibra',
            'is_managed' => true, 'kreen_slug' => 'lkbb-2030', 'body' => 'Isi artikel lomba',
            'id_user' => $admin->id_user,
        ]);
        $url = route('dashboard.content.competitions.show', $listing);

        $this->actingAs($admin)->get(route('dashboard.content.competitions.index'))->assertSee($url);

        $this->actingAs($admin)->get($url)
            ->assertOk()
            ->assertSee(['LKBB Nasional', 'Bandung', 'Purna Paskibra', 'lkbb-2030', 'Isi artikel lomba'])
            ->assertDontSee(route('site.competitions.show', $listing->slug));
    }

    public function test_competition_detail_links_to_public_page_only_when_published(): void
    {
        $admin = $this->admin();
        $listing = CompetitionListing::create(['title' => 'Lomba', 'start_date' => '2030-01-01', 'status' => 'published', 'id_user' => $admin->id_user]);

        $this->actingAs($admin)->get(route('dashboard.content.competitions.show', $listing))
            ->assertOk()
            ->assertSee(route('site.competitions.show', $listing->slug));
    }

    public function test_admin_only_sees_and_manages_their_own_content(): void
    {
        $owner = $this->admin();
        $other = $this->admin();
        $listing = CompetitionListing::create(['title' => 'Lomba Milik Owner', 'start_date' => '2030-01-01', 'status' => 'draft', 'id_user' => $owner->id_user]);
        $agendaItem = AgendaItem::create(['title' => 'Agenda Milik Owner', 'start_at' => '2030-01-01 09:00', 'status' => 'draft', 'id_user' => $owner->id_user]);
        $post = CommunityPost::create(['type' => 'news', 'title' => 'Kabar Milik Owner', 'body' => 'Isi', 'status' => 'draft', 'id_user' => $owner->id_user]);

        $this->actingAs($owner)->get(route('dashboard.content.competitions.index'))->assertSee('Lomba Milik Owner');
        $this->actingAs($owner)->get(route('dashboard.content.agenda.index'))->assertSee('Agenda Milik Owner');
        $this->actingAs($owner)->get(route('dashboard.content.community.index'))->assertSee('Kabar Milik Owner');

        $this->actingAs($other)->get(route('dashboard.content.competitions.index'))->assertDontSee('Lomba Milik Owner');
        $this->actingAs($other)->get(route('dashboard.content.agenda.index'))->assertDontSee('Agenda Milik Owner');
        $this->actingAs($other)->get(route('dashboard.content.community.index'))->assertDontSee('Kabar Milik Owner');

        // Swapping the ID in the URL does not reach another admin's content.
        foreach ([
            route('dashboard.content.competitions.show', $listing),
            route('dashboard.content.competitions.edit', $listing),
            route('dashboard.content.agenda.edit', $agendaItem),
            route('dashboard.content.community.edit', $post),
        ] as $url) {
            $this->actingAs($other)->get($url)->assertForbidden();
        }

        $this->actingAs($other)->put(route('dashboard.content.competitions.update', $listing), $this->listingInput(['title' => 'Dibajak']))->assertForbidden();
        $this->actingAs($other)->delete(route('dashboard.content.competitions.destroy', $listing))->assertForbidden();
        $this->actingAs($other)->delete(route('dashboard.content.agenda.destroy', $agendaItem))->assertForbidden();
        $this->actingAs($other)->delete(route('dashboard.content.community.destroy', $post))->assertForbidden();

        $this->assertSame('Lomba Milik Owner', $listing->fresh()->title);
        $this->assertDatabaseCount('competition_listings', 1);
        $this->assertDatabaseCount('agenda_items', 1);
        $this->assertDatabaseCount('community_posts', 1);
    }

    public function test_organizer_cannot_open_competition_detail(): void
    {
        $listing = CompetitionListing::create(['title' => 'Lomba', 'start_date' => '2030-01-01', 'status' => 'draft']);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard.content.competitions.show', $listing))
            ->assertForbidden();
    }

    public function test_command_grants_and_revokes_platform_admin(): void
    {
        $user = User::factory()->create(['email' => 'ersa@example.com']);

        $this->artisan('siapgrak:platform-admin', ['email' => 'ersa@example.com'])->assertSuccessful();
        $this->assertTrue((bool) $user->fresh()->is_platform_admin);

        $this->artisan('siapgrak:platform-admin', ['email' => 'ersa@example.com', '--revoke' => true])->assertSuccessful();
        $this->assertFalse((bool) $user->fresh()->is_platform_admin);

        $this->artisan('siapgrak:platform-admin', ['email' => 'tidak-ada@example.com'])->assertFailed();
    }

    public function test_admin_can_search_their_own_content_list(): void
    {
        $admin = $this->admin();
        CompetitionListing::create(['title' => 'LKBB Piala Garuda', 'start_date' => '2030-01-01', 'status' => 'draft', 'id_user' => $admin->id_user]);
        CompetitionListing::create(['title' => 'Lomba Tata Upacara', 'start_date' => '2030-01-01', 'status' => 'draft', 'id_user' => $admin->id_user]);

        $this->actingAs($admin)
            ->get(route('dashboard.content.competitions.index', ['q' => 'garuda']))
            ->assertOk()
            ->assertSee('LKBB Piala Garuda')
            ->assertDontSee('Lomba Tata Upacara');
    }

    public function test_agenda_poster_must_be_4_by_5_and_is_removed_with_the_agenda(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $input = ['title' => 'Latihan Gabungan', 'start_at' => '2030-01-01 08:00', 'status' => 'published'];

        $this->actingAs($admin)
            ->post(route('dashboard.content.agenda.store'), $input + ['cover' => UploadedFile::fake()->image('wide.jpg', 1200, 675)])
            ->assertSessionHasErrors('cover');

        $this->actingAs($admin)
            ->post(route('dashboard.content.agenda.store'), $input + ['cover' => UploadedFile::fake()->image('poster.jpg', 1080, 1350)])
            ->assertSessionHasNoErrors();

        $agendaItem = AgendaItem::firstOrFail();
        Storage::disk('public')->assertExists($agendaItem->cover_path);

        $this->actingAs($admin)->delete(route('dashboard.content.agenda.destroy', $agendaItem));
        Storage::disk('public')->assertMissing($agendaItem->cover_path);
    }
}
