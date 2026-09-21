<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by ResultService::finalize()/publish()/reopen() when the round has
 * no competition_results rows yet — organizer must run "Hitung Ulang Hasil"
 * at least once first.
 */
class NoResultsForRound extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Belum ada hasil untuk round ini — hitung hasil dulu sebelum lanjut.');
    }
}
