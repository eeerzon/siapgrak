<?php

namespace Tests\Feature\Registration;

use App\Enums\OrganizationRole;
use App\Enums\RegistrationStatus;
use App\Enums\Status;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F6 — Participants list quick-view drawer (frontend-plan.md §Participants):
 * team/category/status/competition number/roster count/documents, with a
 * link out to the full page for actual edits.
 */
class IndexQuickViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected Event $event;

    protected EventCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $this->event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $this->category = EventCategory::factory()->create(['event_id' => $this->event->id]);
    }

    public function test_list_shows_competition_number_column(): void
    {
        $team = Team::factory()->create(['organization_id' => $this->organization->id]);
        $registration = Registration::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
            'team_id' => $team->id,
        ]);
        CompetitionEntry::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
            'registration_id' => $registration->id,
            'team_id' => $team->id,
            'competition_number' => 'A07',
        ]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.registrations.index', $this->event));

        $response->assertOk();
        $response->assertSee('A07');
        $response->assertSee('No. Kompetisi');
    }

    public function test_registration_without_a_competition_entry_renders_without_error(): void
    {
        $registration = Registration::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
        ]);
        $this->assertNull($registration->entry);

        $this->actingAs($this->owner)
            ->get(route('dashboard.events.registrations.index', $this->event))
            ->assertOk();
    }

    public function test_drawer_body_lists_roster_count_and_document_types(): void
    {
        $registration = Registration::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
        ]);
        $registration->members()->create(['name' => 'Andi', 'member_role' => 'Danton']);
        $registration->members()->create(['name' => 'Budi', 'member_role' => 'Anggota']);
        RegistrationDocument::factory()->create(['registration_id' => $registration->id, 'document_type' => 'surat_tugas']);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.registrations.index', $this->event));

        $response->assertOk();
        $response->assertSee('Surat Tugas');
        $response->assertSee('2 orang');
        $response->assertSee('Buka Halaman Lengkap');
        $response->assertSee(route('dashboard.events.registrations.show', [$this->event, $registration]), false);
    }

    public function test_registration_without_documents_shows_the_empty_message(): void
    {
        Registration::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.registrations.index', $this->event));

        $response->assertOk();
        $response->assertSee('Belum ada dokumen diunggah.');
    }

    public function test_member_role_can_still_view_the_list_and_drawer_content(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);
        Registration::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
            'status' => RegistrationStatus::Submitted,
        ]);

        $this->actingAs($member)
            ->get(route('dashboard.events.registrations.index', $this->event))
            ->assertOk();
    }
}
