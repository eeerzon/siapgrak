<?php

namespace App\Enums;

enum CompetitionLevel: string
{
    case Sd = 'sd';
    case Smp = 'smp';
    case Sma = 'sma';
    case Umum = 'umum';

    public function label(): string
    {
        return match ($this) {
            self::Sd => 'SD/MI',
            self::Smp => 'SMP/MTs',
            self::Sma => 'SMA/SMK/MA',
            self::Umum => 'Umum',
        };
    }
}
