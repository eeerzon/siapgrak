<?php

namespace App\Exceptions;

use App\Enums\EventStatus;
use RuntimeException;

class InvalidEventStatusTransition extends RuntimeException
{
    public function __construct(EventStatus $from, EventStatus $to)
    {
        parent::__construct("Tidak bisa mengubah status event dari \"{$from->value}\" ke \"{$to->value}\".");
    }
}
