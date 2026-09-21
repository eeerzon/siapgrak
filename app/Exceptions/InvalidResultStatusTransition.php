<?php

namespace App\Exceptions;

use App\Enums\ResultStatus;
use RuntimeException;

/**
 * Thrown by ResultService when a finalize/publish/reopen/recalculate is
 * asked for a transition that isn't valid from the round's current result
 * status. See fr-awards-finalization.md Keputusan B/C.
 */
class InvalidResultStatusTransition extends RuntimeException
{
    public function __construct(ResultStatus $from, ResultStatus $to)
    {
        parent::__construct("Tidak bisa mengubah status hasil dari \"{$from->value}\" ke \"{$to->value}\".");
    }
}
