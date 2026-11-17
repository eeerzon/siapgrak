<?php

namespace App\Exceptions;

use App\Enums\RegistrationStatus;
use RuntimeException;

class InvalidRegistrationStatusTransition extends RuntimeException
{
    public function __construct(RegistrationStatus $from, RegistrationStatus $to)
    {
        parent::__construct("Tidak bisa mengubah status pendaftaran dari \"{$from->value}\" ke \"{$to->value}\".");
    }
}
