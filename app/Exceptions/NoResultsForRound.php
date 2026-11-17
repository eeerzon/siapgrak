<?php

namespace App\Exceptions;

use RuntimeException;

class NoResultsForRound extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Belum ada hasil untuk round ini — hitung hasil dulu sebelum lanjut.');
    }
}
