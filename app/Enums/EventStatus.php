<?php

namespace App\Enums;

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
