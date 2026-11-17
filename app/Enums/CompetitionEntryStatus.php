<?php

namespace App\Enums;

enum CompetitionEntryStatus: string
{
    case Registered = 'registered';
    case CheckedIn = 'checked_in';
    case Ready = 'ready';
    case OnDeck = 'on_deck';
    case Performing = 'performing';
    case Finished = 'finished';
    case Withdrawn = 'withdrawn';
    case Disqualified = 'disqualified';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Terdaftar',
            self::CheckedIn => 'Sudah registrasi ulang',
            self::Ready => 'Siap tampil',
            self::OnDeck => 'Bersiap',
            self::Performing => 'Sedang tampil',
            self::Finished => 'Selesai tampil',
            self::Withdrawn => 'Mengundurkan diri',
            self::Disqualified => 'Didiskualifikasi',
        };
    }
}
