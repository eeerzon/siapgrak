<?php

namespace App\Exceptions;

use App\Enums\ResultStatus;
use RuntimeException;

class InvalidResultStatusTransition extends RuntimeException
{
    public function __construct(ResultStatus $from, ResultStatus $to)
    {
        parent::__construct("Tidak bisa mengubah status hasil dari \"{$from->value}\" ke \"{$to->value}\".");
    }
}
