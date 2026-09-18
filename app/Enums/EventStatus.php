<?php

namespace App\Enums;

/**
 * Event lifecycle. Transitions are explicit and manual (never auto-derived
 * from dates) — the allowed transition map lives in EventService::changeStatus().
 * `published` means "confirmed & announced internally" while Admin v1 has no
 * public site. See fr-event-category.md Keputusan B.
 */
enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case RegistrationOpen = 'registration_open';
    case RegistrationClosed = 'registration_closed';
    case Ongoing = 'ongoing';
    case Finished = 'finished';
    case Cancelled = 'cancelled';
}
