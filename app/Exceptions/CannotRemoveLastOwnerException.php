<?php

namespace App\Exceptions;

use RuntimeException;

class CannotRemoveLastOwnerException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Organisasi harus memiliki minimal satu owner.');
    }
}
