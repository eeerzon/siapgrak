<?php

namespace Tests\Feature\Registration;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Adversarial: "How can User A see, create, review, or download registrations
 * and documents belonging to Event B?" Mirrors Event/TenantIsolationTest.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected Event $eventA;

    protected Registration $registrationA;

    protected User $userB;

    protected Event $eventB;

    protected Registration $registrationB;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        [$this->userA, $this->eventA, $this->registrationA] = $this->world();
        [$this->userB, $this->eventB, $this->registrationB] = $this->world();
    }

    /**
     * @return array{0: User, 1: Event, 2: Registration}
     */
    protected function world(): array
    {
        $user = User::factory()->create();
        $org = Organization::factory()->create();
        $org->users()->attach($user, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $category = EventCategory::factory()->create(['event_id' => $event->id]);
        $team = Team::factory()->create(['organization_id' => $org->id]);
        $registration = Registration::factory()->create([
            'event_id' => $event->id,
            'event_category_id' => $category->id,
            'team_id' => $team->id,
        ]);

        return [$user, $event, $registration];
    }

    public function test_user_a_cannot_view_event_b_registration_list(): void
    {
        $this->actingAs($this->userA)
            ->get(route('dashboard.events.registrations.index', $this->eventB))
            ->assertForbidden();
    }

    public function test_registration_list_only_shows_own_org(): void
    {
        $response = $this->actingAs($this->userA)->get(route('dashboard.events.registrations.index', $this->eventA));
        $response->assertOk();
        $response->assertSee($this->registrationA->team->name);
        $response->assertDontSee($this->registrationB->team->name);
    }

    public function test_user_a_cannot_view_registration_b(): void
    {
        $this->actingAs($this->userA)
            ->get(route('dashboard.events.registrations.show', [$this->eventB, $this->registrationB]))
            ->assertForbidden();
    }

    public function test_user_a_cannot_change_status_of_registration_b(): void
    {
        $this->actingAs($this->userA)
            ->patch(route('dashboard.events.registrations.status', [$this->eventB, $this->registrationB]), ['status' => 'submitted'])
            ->assertForbidden();
    }

    public function test_user_a_cannot_add_roster_or_document_to_registration_b(): void
    {
        $this->actingAs($this->userA)
            ->post(route('dashboard.registrations.members.store', $this->registrationB), ['name' => 'Intruder'])
            ->assertForbidden();

        $this->actingAs($this->userA)
            ->post(route('dashboard.registrations.documents.store', $this->registrationB), [
                'document_type' => 'surat_tugas',
                'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_user_a_cannot_download_registration_b_document(): void
    {
        $this->actingAs($this->userB)->post(route('dashboard.registrations.documents.store', $this->registrationB), [
            'document_type' => 'surat_tugas',
            'file' => UploadedFile::fake()->create('secret.pdf', 10, 'application/pdf'),
        ]);
        $document = $this->registrationB->documents()->firstOrFail();

        $this->actingAs($this->userA)
            ->get(route('dashboard.registrations.documents.download', [$this->registrationB, $document]))
            ->assertForbidden();
    }

    public function test_child_not_belonging_to_parent_is_not_found(): void
    {
        // registration B's route param swapped under event A
        $this->actingAs($this->userA)
            ->get(route('dashboard.events.registrations.show', [$this->eventA, $this->registrationB]))
            ->assertNotFound();

        // userA may manage registration A, but the member id actually belongs
        // to registration B — the controller must reject it independently.
        $memberInB = $this->registrationB->members()->create(['name' => 'Roster B']);
        $this->actingAs($this->userA)
            ->delete(route('dashboard.registrations.members.destroy', [$this->registrationA, $memberInB]))
            ->assertNotFound();

        $this->assertDatabaseHas('registration_members', ['id' => $memberInB->id]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard.events.registrations.index', $this->eventA))->assertRedirect(route('login'));
        $this->get(route('dashboard.events.registrations.show', [$this->eventA, $this->registrationA]))->assertRedirect(route('login'));
    }
}
