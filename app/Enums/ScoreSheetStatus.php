<?php

namespace App\Enums;

enum ScoreSheetStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Terkirim',
            self::Locked => 'Dikunci',
        };
    }
}
