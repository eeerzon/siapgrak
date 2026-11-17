<?php

namespace App\Enums;

enum AwardSource: string
{
    case Judging = 'judging';
    case Manual = 'manual';
    case KreenconnectVoting = 'kreenconnect_voting';
}
