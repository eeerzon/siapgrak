<?php

namespace App\Exceptions;

use App\Enums\CompetitionEntryStatus;
use RuntimeException;

class InvalidCompetitionEntryStatusTransition extends RuntimeException
{
    public function __construct(CompetitionEntryStatus $from, CompetitionEntryStatus $to)
    {
        parent::__construct("Tidak bisa mengubah status entry dari \"{$from->value}\" ke \"{$to->value}\".");
    }
}
