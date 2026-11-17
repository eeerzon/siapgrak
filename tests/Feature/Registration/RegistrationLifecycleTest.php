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
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RegistrationLifecycleTest extends TestCase
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

    protected function registration(RegistrationStatus $status = RegistrationStatus::Draft): Registration
    {
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);

        return Registration::factory()->create([
            'event_id' => $event->id,
            'status' => $status,
        ]);
    }

    protected function changeStatus(Registration $registration, string $status, array $extra = []): TestResponse
    {
        return $this->actingAs($this->owner)->patch(
            route('dashboard.events.registrations.status', [$registration->event_id, $registration]),
            array_merge(['status' => $status], $extra),
        );
    }

    public function test_draft_to_submitted_stamps_submitted_at_once(): void
    {
        $registration = $this->registration(RegistrationStatus::Draft);

        $this->changeStatus($registration, 'submitted')
            ->assertRedirect(route('dashboard.events.registrations.show', [$registration->event_id, $registration]));

        $registration->refresh();
        $this->assertSame(RegistrationStatus::Submitted, $registration->status);
        $this->assertNotNull($registration->submitted_at);
        $first = $registration->submitted_at;

        // submitted -> withdrawn -> draft -> submitted again: not reset
        $this->changeStatus($registration, 'withdrawn');
        $this->changeStatus($registration->refresh(), 'draft');
        $this->changeStatus($registration->refresh(), 'submitted');

        $this->assertTrue($first->equalTo($registration->refresh()->submitted_at));
    }

    public function test_submitted_to_approved_stamps_reviewer(): void
    {
        $registration = $this->registration(RegistrationStatus::Submitted);

        $this->changeStatus($registration, 'approved');

        $registration->refresh();
        $this->assertSame(RegistrationStatus::Approved, $registration->status);
        $this->assertNotNull($registration->reviewed_at);
        $this->assertSame($this->owner->id_user, $registration->reviewed_by);
    }

    public function test_reject_and_request_revision_require_notes(): void
    {
        $registration = $this->registration(RegistrationStatus::Submitted);

        $this->changeStatus($registration, 'rejected')->assertSessionHasErrors('review_notes');
        $this->changeStatus($registration, 'revision_requested')->assertSessionHasErrors('review_notes');
        $this->assertSame(RegistrationStatus::Submitted, $registration->fresh()->status);

        $this->changeStatus($registration, 'revision_requested', ['review_notes' => 'Lengkapi kartu pelajar 2 orang.']);
        $this->assertSame(RegistrationStatus::RevisionRequested, $registration->fresh()->status);
        $this->assertSame('Lengkapi kartu pelajar 2 orang.', $registration->fresh()->review_notes);
    }

    public function test_revision_requested_and_rejected_can_be_resubmitted(): void
    {
        $r1 = $this->registration(RegistrationStatus::RevisionRequested);
        $this->changeStatus($r1, 'submitted');
        $this->assertSame(RegistrationStatus::Submitted, $r1->fresh()->status);

        $r2 = $this->registration(RegistrationStatus::Rejected);
        $this->changeStatus($r2, 'submitted');
        $this->assertSame(RegistrationStatus::Submitted, $r2->fresh()->status);
    }

    public function test_withdrawn_can_be_reactivated_to_draft(): void
    {
        $registration = $this->registration(RegistrationStatus::Withdrawn);

        $this->changeStatus($registration, 'draft');

        $this->assertSame(RegistrationStatus::Draft, $registration->fresh()->status);
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $draft = $this->registration(RegistrationStatus::Draft);
        $this->changeStatus($draft, 'approved')->assertSessionHasErrors('status');
        $this->assertSame(RegistrationStatus::Draft, $draft->fresh()->status);

        $approved = $this->registration(RegistrationStatus::Approved);
        $this->changeStatus($approved, 'draft')->assertSessionHasErrors('status');
        $this->assertSame(RegistrationStatus::Approved, $approved->fresh()->status);
    }

    public function test_member_role_cannot_change_status(): void
    {
        $member = User::factory()->create();
        $this->organization->users()->attach($member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);
        $registration = $this->registration(RegistrationStatus::Draft);

        $this->actingAs($member)
            ->patch(route('dashboard.events.registrations.status', [$registration->event_id, $registration]), ['status' => 'submitted'])
            ->assertForbidden();

        $this->assertSame(RegistrationStatus::Draft, $registration->fresh()->status);
    }
}
