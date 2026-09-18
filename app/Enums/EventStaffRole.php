<?php

namespace App\Enums;

/**
 * Role label on an event_staff row. Recorded in Sprint 3 but NOT an
 * authorization source yet — gating happens in the sprints that need each
 * role (registration_staff → Sprint 4, scoring_admin → Sprint 9-10).
 * See fr-event-category.md Keputusan A.
 */
enum EventStaffRole: string
{
    case EventAdmin = 'event_admin';
    case RegistrationStaff = 'registration_staff';
    case CompetitionStaff = 'competition_staff';
    case ScoringAdmin = 'scoring_admin';
    case Viewer = 'viewer';
}
