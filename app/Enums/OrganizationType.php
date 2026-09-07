<?php

namespace App\Enums;

enum OrganizationType: string
{
    case School = 'school';
    case Association = 'association';
    case Community = 'community';
    case Organizer = 'organizer';
    case Agency = 'agency';
    case Other = 'other';
}
