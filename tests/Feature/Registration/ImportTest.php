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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected Event $event;

    protected EventCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $this->event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $this->category = EventCategory::factory()->create(['event_id' => $this->event->id]);
    }

    protected function uploadAndMap(string $content, ?string $filename = 'peserta.csv'): array
    {
        $upload = $this->actingAs($this->owner)->post(route('dashboard.events.registrations.import.store', $this->event), [
            'event_category_id' => $this->category->id,
            'file' => UploadedFile::fake()->createWithContent($filename, $content),
        ]);

        $redirect = $upload->headers->get('Location');
        preg_match('#/import/([^/]+)/map#', $redirect, $matches);

        return ['token' => $matches[1] ?? null, 'response' => $upload];
    }

    public function test_upload_valid_file_redirects_to_mapping_page_with_headers_detected(): void
    {
        $content = "Tim,Nama,JK,Posisi\nTeam A,Andi,L,Danton\n";
        ['token' => $token] = $this->uploadAndMap($content);

        $this->assertNotNull($token);

        $this->actingAs($this->owner)
            ->get(route('dashboard.events.registrations.import.map', [$this->event, $token]))
            ->assertOk()
            ->assertSee('Tim')
            ->assertSee('Nama')
            ->assertSee('JK')
            ->assertSee('Posisi');
    }

    public function test_upload_non_csv_file_is_rejected(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.events.registrations.import.store', $this->event), [
            'event_category_id' => $this->category->id,
            'file' => UploadedFile::fake()->create('peserta.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('file');
    }

    public function test_mapping_page_guesses_similar_headers_correctly(): void
    {
        $content = "Tim,Nama,JK,Posisi\nTeam A,Andi,L,Danton\n";
        ['token' => $token] = $this->uploadAndMap($content);

        $response = $this->actingAs($this->owner)
            ->get(route('dashboard.events.registrations.import.map', [$this->event, $token]));

        $response->assertOk();
        $guessed = $response->viewData('guessed');

        $this->assertSame('Tim', $guessed['team_name']);
        $this->assertSame('Nama', $guessed['member_name']);
        $this->assertSame('JK', $guessed['gender']);
        $this->assertSame('Posisi', $guessed['member_role']);
    }

    protected function preview(string $token, string $content): TestResponse
    {
        return $this->actingAs($this->owner)->post(route('dashboard.events.registrations.import.preview', [$this->event, $token]), [
            'event_category_id' => $this->category->id,
            'team_name' => 'Tim',
            'member_name' => 'Nama',
            'gender' => 'JK',
            'member_role' => 'Posisi',
        ]);
    }

    public function test_preview_marks_group_invalid_when_team_not_found(): void
    {
        $content = "Tim,Nama,JK,Posisi\nTim Tak Dikenal,Andi,L,Danton\n";
        ['token' => $token] = $this->uploadAndMap($content);

        $response = $this->preview($token, $content);
        $groups = $response->viewData('groups');

        $this->assertCount(1, $groups);
        $this->assertFalse($groups[0]['valid']);
        $this->assertFalse($groups[0]['exists']);
    }

    public function test_preview_marks_group_invalid_when_team_already_registered_in_category(): void
    {
        $team = Team::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Team A']);
        $registration = Registration::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
            'team_id' => $team->id,
            'status' => RegistrationStatus::Submitted,
        ]);
        $this->assertNotNull($registration->id);

        $content = "Tim,Nama,JK,Posisi\nTeam A,Andi,L,Danton\n";
        ['token' => $token] = $this->uploadAndMap($content);

        $response = $this->preview($token, $content);
        $groups = $response->viewData('groups');

        $this->assertCount(1, $groups);
        $this->assertFalse($groups[0]['valid']);
        $this->assertTrue($groups[0]['duplicate']);
    }

    public function test_preview_skips_only_the_row_with_empty_member_name(): void
    {
        Team::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Team A']);

        $content = "Tim,Nama,JK,Posisi\nTeam A,Andi,L,Danton\nTeam A,,L,Anggota\n";
        ['token' => $token] = $this->uploadAndMap($content);

        $response = $this->preview($token, $content);
        $groups = $response->viewData('groups');

        $this->assertCount(1, $groups);
        $this->assertTrue($groups[0]['valid']);
        $this->assertCount(2, $groups[0]['members']);
        $this->assertTrue($groups[0]['members'][0]['valid']);
        $this->assertFalse($groups[0]['members'][1]['valid']);
    }

    protected function commit(string $token, string $content): TestResponse
    {
        return $this->actingAs($this->owner)->post(route('dashboard.events.registrations.import.commit', [$this->event, $token]), [
            'event_category_id' => $this->category->id,
            'team_name' => 'Tim',
            'member_name' => 'Nama',
            'gender' => 'JK',
            'member_role' => 'Posisi',
        ]);
    }

    public function test_commit_creates_registration_directly_as_submitted(): void
    {
        Team::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Team A']);

        $content = "Tim,Nama,JK,Posisi\nTeam A,Andi,L,Danton\n";
        ['token' => $token] = $this->uploadAndMap($content);

        $this->commit($token, $content)->assertRedirect(route('dashboard.events.registrations.index', $this->event));

        $registration = Registration::where('event_category_id', $this->category->id)->firstOrFail();
        $this->assertSame(RegistrationStatus::Submitted, $registration->status);
        $this->assertNotNull($registration->submitted_at);
    }

    public function test_commit_creates_roster_members_with_null_team_member_id(): void
    {
        Team::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Team A']);

        $content = "Tim,Nama,JK,Posisi\nTeam A,Andi,L,Danton\nTeam A,Budi,L,Anggota\n";
        ['token' => $token] = $this->uploadAndMap($content);

        $this->commit($token, $content);

        $registration = Registration::where('event_category_id', $this->category->id)->firstOrFail();
        $this->assertCount(2, $registration->members);
        $this->assertNull($registration->members->first()->team_member_id);
        $this->assertSame('Andi', $registration->members->first()->name);
        $this->assertSame('Danton', $registration->members->first()->member_role);
    }

    public function test_commit_skips_invalid_groups_without_creating_registration(): void
    {
        $content = "Tim,Nama,JK,Posisi\nTim Tak Dikenal,Andi,L,Danton\n";
        ['token' => $token] = $this->uploadAndMap($content);

        $result = $this->commit($token, $content);
        $result->assertSessionHas('success');

        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_commit_is_partial_across_valid_and_invalid_groups_in_one_file(): void
    {
        Team::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Team A']);

        $content = "Tim,Nama,JK,Posisi\nTeam A,Andi,L,Danton\nTim Tak Dikenal,Budi,L,Anggota\n";
        ['token' => $token] = $this->uploadAndMap($content);

        $this->commit($token, $content);

        $this->assertDatabaseCount('registrations', 1);
        $registration = Registration::firstOrFail();
        $this->assertSame('Andi', $registration->members->first()->name);
    }

    public function test_semicolon_delimiter_is_auto_detected(): void
    {
        Team::factory()->create(['organization_id' => $this->organization->id, 'name' => 'Team A']);

        $content = "Tim;Nama;JK;Posisi\nTeam A;Andi;L;Danton\n";
        ['token' => $token] = $this->uploadAndMap($content);

        $response = $this->actingAs($this->owner)
            ->get(route('dashboard.events.registrations.import.map', [$this->event, $token]));

        $guessed = $response->viewData('guessed');
        $this->assertSame('Tim', $guessed['team_name']);

        $preview = $this->preview($token, $content);
        $groups = $preview->viewData('groups');
        $this->assertTrue($groups[0]['valid']);
    }

    public function test_member_role_cannot_access_import_flow(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);

        $this->actingAs($member)
            ->get(route('dashboard.events.registrations.import.create', $this->event))
            ->assertForbidden();

        $this->actingAs($member)->post(route('dashboard.events.registrations.import.store', $this->event), [
            'event_category_id' => $this->category->id,
            'file' => UploadedFile::fake()->createWithContent('peserta.csv', "Tim,Nama\nTeam A,Andi\n"),
        ])->assertForbidden();
    }
}
