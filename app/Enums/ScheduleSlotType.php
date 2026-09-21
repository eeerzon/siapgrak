<?php

namespace App\Enums;

enum ScheduleSlotType: string
{
    case Competition = 'competition';
    case TechnicalMeeting = 'technical_meeting';
    case Break = 'break';
    case Ceremony = 'ceremony';
    case Announcement = 'announcement';
    case Other = 'other';
}
