<?php

namespace App\Enums;

enum CommunityPostType: string
{
    case Achievement = 'achievement';
    case News = 'news';

    public function label(): string
    {
        return match ($this) {
            self::Achievement => 'Prestasi',
            self::News => 'Kabar Komunitas',
        };
    }
}
