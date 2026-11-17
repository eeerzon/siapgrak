<?php

namespace App\Enums;

enum EventStaffRole: string
{
    case EventAdmin = 'event_admin';
    case RegistrationStaff = 'registration_staff';
    case CompetitionStaff = 'competition_staff';
    case ScoringAdmin = 'scoring_admin';
    case Viewer = 'viewer';
}
