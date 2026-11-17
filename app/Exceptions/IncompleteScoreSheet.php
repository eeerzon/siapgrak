<?php

namespace App\Exceptions;

use App\Models\JudgingCriteria;
use Illuminate\Support\Collection;
use RuntimeException;

class IncompleteScoreSheet extends RuntimeException
{
    /**
     * @param  Collection<int, JudgingCriteria>  $missing
     */
    public function __construct(Collection $missing)
    {
        $names = $missing->pluck('name')->implode(', ');

        parent::__construct("Belum semua kriteria diisi: {$names}.");
    }
}
