<?php

namespace App\Enums;

/**
 * Shared active/inactive status used by organizations and organization_users.
 * status=inactive is a soft-disable, not a delete — see CLAUDE.md Constitution.
 */
enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
