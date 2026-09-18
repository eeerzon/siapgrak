<?php

namespace App\Enums;

/**
 * Event-day lifecycle of a competition entry. Transitions are explicit —
 * see the allowed-transition map in CompetitionService::changeStatus() and
 * fr-competition-entry.md Keputusan G.
 */
enum CompetitionEntryStatus: string
{
    case Registered = 'registered';
    case CheckedIn = 'checked_in';
    case Ready = 'ready';
    case OnDeck = 'on_deck';
    case Performing = 'performing';
    case Finished = 'finished';
    case Withdrawn = 'withdrawn';
    case Disqualified = 'disqualified';
}
