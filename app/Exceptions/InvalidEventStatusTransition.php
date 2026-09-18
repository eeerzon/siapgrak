<?php

namespace App\Exceptions;

use App\Enums\EventStatus;
use RuntimeException;

/**
 * Thrown when EventService::changeStatus() is asked for a transition that is
 * not in the allowed map. See fr-event-category.md Keputusan B. Rendered as a
 * 422 (see bootstrap/app.php exception handling / the controller catch).
 */
class InvalidEventStatusTransition extends RuntimeException
{
    public function __construct(EventStatus $from, EventStatus $to)
    {
        parent::__construct("Tidak bisa mengubah status event dari \"{$from->value}\" ke \"{$to->value}\".");
    }
}
