<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Exceptions\InvalidRegistrationStatusTransition;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;

class RegistrationService
{
    public function __construct(protected CompetitionService $competition, protected ActivityLogger $activityLogger) {}

    /**
     * Allowed status transitions (fr-registration.md Keputusan B). A target
     * not listed for the current status is rejected.
     *
     * @var array<string, list<RegistrationStatus>>
     */
    protected const TRANSITIONS = [
        'draft' => [RegistrationStatus::Submitted, RegistrationStatus::Withdrawn],
        'submitted' => [
            RegistrationStatus::Approved,
            RegistrationStatus::Rejected,
            RegistrationStatus::RevisionRequested,
            RegistrationStatus::Withdrawn,
        ],
        'revision_requested' => [RegistrationStatus::Submitted, RegistrationStatus::Withdrawn],
        'approved' => [RegistrationStatus::Withdrawn],
        'rejected' => [RegistrationStatus::Submitted, RegistrationStatus::Withdrawn],
        'withdrawn' => [RegistrationStatus::Draft],
    ];

    /**
     * Statuses that count as a "review outcome" — they stamp reviewed_at/by.
     *
     * @var list<RegistrationStatus>
     */
    protected const REVIEW_OUTCOMES = [
        RegistrationStatus::Approved,
        RegistrationStatus::Rejected,
        RegistrationStatus::RevisionRequested,
    ];

    public function create(Event $event, int $eventCategoryId, int $teamId, User $enteredBy): Registration
    {
        $registration = new Registration([
            'event_id' => $event->id,
            'event_category_id' => $eventCategoryId,
            'team_id' => $teamId,
        ]);
        $registration->submitted_by = $enteredBy->id;
        $registration->status = RegistrationStatus::Draft;
        $registration->save();

        return $registration;
    }

    /**
     * @return list<RegistrationStatus>
     */
    public function allowedTransitions(RegistrationStatus $from): array
    {
        return self::TRANSITIONS[$from->value];
    }

    public function changeStatus(Registration $registration, RegistrationStatus $to, User $actor, ?string $reviewNotes = null): Registration
    {
        $from = $registration->status;

        if ($from === $to || ! in_array($to, self::TRANSITIONS[$from->value], true)) {
            throw new InvalidRegistrationStatusTransition($from, $to);
        }

        $registration->status = $to;

        if ($to === RegistrationStatus::Submitted && $registration->submitted_at === null) {
            $registration->submitted_at = now();
        }

        if (in_array($to, self::REVIEW_OUTCOMES, true)) {
            $registration->reviewed_at = now();
            $registration->reviewed_by = $actor->id;
            $registration->review_notes = $reviewNotes;
        }

        $registration->save();

        // Sprint 5 hooks (fr-competition-entry.md Keputusan A/B): approval
        // creates the competition entry (idempotent); withdrawal cascades a
        // non-terminal entry to withdrawn too, so the two never drift apart.
        if ($to === RegistrationStatus::Approved) {
            $this->competition->createEntryForRegistration($registration);
        } elseif ($to === RegistrationStatus::Withdrawn) {
            $this->competition->syncForRegistrationWithdrawal($registration);
        }

        // Sprint 13 audit trail retrofit (fr-operational-polish.md) — only
        // the two canonical review outcomes get logged, matching the action
        // list promised in Sprint 12; other transitions (submitted,
        // withdrawn, revision_requested) are not part of that list.
        if ($to === RegistrationStatus::Approved) {
            $this->activityLogger->log('registration_approved', $registration->event, $registration);
        } elseif ($to === RegistrationStatus::Rejected) {
            $this->activityLogger->log('registration_rejected', $registration->event, $registration, ['review_notes' => $reviewNotes]);
        }

        return $registration;
    }
}
