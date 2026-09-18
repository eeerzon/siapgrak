<?php

namespace Tests\Feature\CompetitionEntry;

use App\Enums\CompetitionEntryStatus;
use App\Enums\OrganizationRole;
use App\Enums\RegistrationStatus;
use App\Enums\Status;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoCreateEntryTest extends TestCase
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

    protected function submittedRegistration(): Registration
    {
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);

        return Registration::factory()->create(['event_id' => $event->id, 'status' => RegistrationStatus::Submitted]);
    }

    protected function changeStatus(Registration $registration, string $status): void
    {
        $this->actingAs($this->owner)->patch(
            route('dashboard.events.registrations.status', [$registration->event_id, $registration]),
            ['status' => $status],
        );
    }

    public function test_approving_a_registration_creates_a_competition_entry(): void
    {
        $registration = $this->submittedRegistration();

        $this->changeStatus($registration, 'approved');

        $entry = CompetitionEntry::where('registration_id', $registration->id)->firstOrFail();
        $this->assertSame($registration->event_id, $entry->event_id);
        $this->assertSame($registration->event_category_id, $entry->event_category_id);
        $this->assertSame($registration->team_id, $entry->team_id);
        $this->assertSame(CompetitionEntryStatus::Registered, $entry->status);
        $this->assertStringStartsWith('entry_', $entry->public_id);
    }

    public function test_reapproving_a_reactivated_registration_does_not_duplicate_the_entry(): void
    {
        $registration = $this->submittedRegistration();
        $this->changeStatus($registration, 'approved');
        $firstEntryId = CompetitionEntry::where('registration_id', $registration->id)->firstOrFail()->id;

        $this->changeStatus($registration->refresh(), 'withdrawn');
        $this->changeStatus($registration->refresh(), 'draft');
        $this->changeStatus($registration->refresh(), 'submitted');
        $this->changeStatus($registration->refresh(), 'approved');

        $this->assertSame(1, CompetitionEntry::where('registration_id', $registration->id)->count());
        $this->assertSame($firstEntryId, CompetitionEntry::where('registration_id', $registration->id)->firstOrFail()->id);
    }

    public function test_withdrawing_an_approved_registration_withdraws_its_entry(): void
    {
        $registration = $this->submittedRegistration();
        $this->changeStatus($registration, 'approved');

        $this->changeStatus($registration->refresh(), 'withdrawn');

        $entry = CompetitionEntry::where('registration_id', $registration->id)->firstOrFail();
        $this->assertSame(CompetitionEntryStatus::Withdrawn, $entry->status);
    }

    public function test_finished_entry_is_not_overridden_by_registration_withdrawal(): void
    {
        $registration = $this->submittedRegistration();
        $this->changeStatus($registration, 'approved');
        $entry = CompetitionEntry::where('registration_id', $registration->id)->firstOrFail();
        // status is intentionally not mass-assignable (set only by the
        // service) — assign directly here, same as production code does.
        $entry->status = CompetitionEntryStatus::Finished;
        $entry->save();

        $this->changeStatus($registration->refresh(), 'withdrawn');

        $this->assertSame(CompetitionEntryStatus::Finished, $entry->fresh()->status);
    }

    public function test_a_registration_never_approved_has_no_entry(): void
    {
        $registration = $this->submittedRegistration();

        $this->actingAs($this->owner)->patch(
            route('dashboard.events.registrations.status', [$registration->event_id, $registration]),
            ['status' => 'rejected', 'review_notes' => 'Dokumen tidak lengkap.'],
        );

        $this->assertSame(RegistrationStatus::Rejected, $registration->fresh()->status);
        $this->assertSame(0, CompetitionEntry::where('registration_id', $registration->id)->count());
    }
}
