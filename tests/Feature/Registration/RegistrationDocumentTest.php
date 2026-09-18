<?php

namespace Tests\Feature\Registration;

use App\Enums\OrganizationRole;
use App\Enums\RegistrationStatus;
use App\Enums\Status;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
    }

    protected function registration(RegistrationStatus $status = RegistrationStatus::Draft): Registration
    {
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);

        return Registration::factory()->create(['event_id' => $event->id, 'status' => $status]);
    }

    public function test_owner_can_upload_a_document_to_the_private_disk(): void
    {
        $registration = $this->registration();

        $this->actingAs($this->owner)->post(route('dashboard.registrations.documents.store', $registration), [
            'document_type' => 'surat_tugas',
            'file' => UploadedFile::fake()->create('surat.pdf', 200, 'application/pdf'),
        ])->assertRedirect(route('dashboard.events.registrations.show', [$registration->event_id, $registration]));

        $document = $registration->documents()->firstOrFail();
        $this->assertSame('surat_tugas', $document->document_type);
        Storage::disk('local')->assertExists($document->file_path);
        // stored under a per-registration folder, not at a guessable public path
        $this->assertStringStartsWith("registration-documents/{$registration->id}/", $document->file_path);
    }

    public function test_disallowed_mime_is_rejected(): void
    {
        $registration = $this->registration();

        $this->actingAs($this->owner)->post(route('dashboard.registrations.documents.store', $registration), [
            'document_type' => 'surat_tugas',
            'file' => UploadedFile::fake()->create('script.php', 10, 'text/x-php'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('registration_documents', 0);
    }

    public function test_owner_can_download_but_outsider_cannot(): void
    {
        $registration = $this->registration();
        $this->actingAs($this->owner)->post(route('dashboard.registrations.documents.store', $registration), [
            'document_type' => 'kartu_pelajar',
            'file' => UploadedFile::fake()->create('kartu.pdf', 50, 'application/pdf'),
        ]);
        $document = $registration->documents()->firstOrFail();

        $this->actingAs($this->owner)
            ->get(route('dashboard.registrations.documents.download', [$registration, $document]))
            ->assertOk();

        $outsider = User::factory()->create();
        $this->actingAs($outsider)
            ->get(route('dashboard.registrations.documents.download', [$registration, $document]))
            ->assertForbidden();
    }

    public function test_cannot_upload_once_submitted(): void
    {
        $registration = $this->registration(RegistrationStatus::Submitted);

        $this->actingAs($this->owner)->post(route('dashboard.registrations.documents.store', $registration), [
            'document_type' => 'surat_tugas',
            'file' => UploadedFile::fake()->create('late.pdf', 10, 'application/pdf'),
        ])->assertForbidden();
    }

    public function test_destroy_removes_row_and_file(): void
    {
        $registration = $this->registration();
        $this->actingAs($this->owner)->post(route('dashboard.registrations.documents.store', $registration), [
            'document_type' => 'pakta_integritas',
            'file' => UploadedFile::fake()->create('pakta.pdf', 20, 'application/pdf'),
        ]);
        $document = $registration->documents()->firstOrFail();
        $path = $document->file_path;

        $this->actingAs($this->owner)->delete(route('dashboard.registrations.documents.destroy', [$registration, $document]));

        $this->assertDatabaseMissing('registration_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_member_role_cannot_upload(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);
        $registration = $this->registration();

        $this->actingAs($member)->post(route('dashboard.registrations.documents.store', $registration), [
            'document_type' => 'surat_tugas',
            'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ])->assertForbidden();
    }
}
