<?php

namespace App\Enums;

/**
 * Registration lifecycle (admin-entry mode). Transitions are explicit and
 * validated by RegistrationService::changeStatus() — see the transition map
 * there and fr-registration.md Keputusan B.
 */
enum RegistrationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case RevisionRequested = 'revision_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
}
