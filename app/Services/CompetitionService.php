<?php

namespace App\Services;

use App\Enums\CompetitionEntryStatus;
use App\Exceptions\InvalidCompetitionEntryStatusTransition;
use App\Models\CompetitionEntry;
use App\Models\Registration;

class CompetitionService
{
    /**
     * Allowed status transitions (fr-competition-entry.md Keputusan G).
     *
     * @var array<string, list<CompetitionEntryStatus>>
     */
    protected const TRANSITIONS = [
        'registered' => [CompetitionEntryStatus::CheckedIn, CompetitionEntryStatus::Withdrawn, CompetitionEntryStatus::Disqualified],
        'checked_in' => [CompetitionEntryStatus::Ready, CompetitionEntryStatus::Withdrawn, CompetitionEntryStatus::Disqualified],
        'ready' => [CompetitionEntryStatus::OnDeck, CompetitionEntryStatus::Withdrawn, CompetitionEntryStatus::Disqualified],
        'on_deck' => [CompetitionEntryStatus::Performing, CompetitionEntryStatus::Withdrawn, CompetitionEntryStatus::Disqualified],
        'performing' => [CompetitionEntryStatus::Finished, CompetitionEntryStatus::Disqualified],
        'finished' => [],
        'withdrawn' => [],
        'disqualified' => [],
    ];

    /**
     * Terminal states a registration-withdrawal must never override
     * (fr-competition-entry.md Keputusan B).
     *
     * @var list<CompetitionEntryStatus>
     */
    protected const TERMINAL = [
        CompetitionEntryStatus::Finished,
        CompetitionEntryStatus::Withdrawn,
        CompetitionEntryStatus::Disqualified,
    ];

    /**
     * Called from RegistrationService when a registration becomes approved.
     * Idempotent by registration_id — re-approving a reactivated registration
     * never creates a second entry (RULE-1).
     */
    public function createEntryForRegistration(Registration $registration): CompetitionEntry
    {
        return CompetitionEntry::firstOrCreate(
            ['registration_id' => $registration->id],
            [
                'event_id' => $registration->event_id,
                'event_category_id' => $registration->event_category_id,
                'team_id' => $registration->team_id,
            ],
        );
    }

    /**
     * Called from RegistrationService when a registration is withdrawn. A
     * cross-domain cascade, not a user-driven transition — it deliberately
     * bypasses the TRANSITIONS map and never touches an already-terminal
     * entry (RULE-2).
     */
    public function syncForRegistrationWithdrawal(Registration $registration): void
    {
        $entry = $registration->entry()->first();

        if ($entry === null || in_array($entry->status, self::TERMINAL, true)) {
            return;
        }

        $entry->status = CompetitionEntryStatus::Withdrawn;
        $entry->save();
    }

    /**
     * @return list<CompetitionEntryStatus>
     */
    public function allowedTransitions(CompetitionEntryStatus $from): array
    {
        return self::TRANSITIONS[$from->value];
    }

    public function changeStatus(CompetitionEntry $entry, CompetitionEntryStatus $to): CompetitionEntry
    {
        $from = $entry->status;

        if ($from === $to || ! in_array($to, self::TRANSITIONS[$from->value], true)) {
            throw new InvalidCompetitionEntryStatusTransition($from, $to);
        }

        $entry->status = $to;

        if ($to === CompetitionEntryStatus::CheckedIn && $entry->checked_in_at === null) {
            $entry->checked_in_at = now();
        }

        $entry->save();

        return $entry;
    }
}
