<?php

namespace App\Exceptions;

use App\Enums\CompetitionEntryStatus;
use RuntimeException;

/**
 * Thrown when CompetitionService::changeStatus() is asked for a transition
 * not in the allowed map (fr-competition-entry.md Keputusan G).
 */
class InvalidCompetitionEntryStatusTransition extends RuntimeException
{
    public function __construct(CompetitionEntryStatus $from, CompetitionEntryStatus $to)
    {
        parent::__construct("Tidak bisa mengubah status entry dari \"{$from->value}\" ke \"{$to->value}\".");
    }
}
