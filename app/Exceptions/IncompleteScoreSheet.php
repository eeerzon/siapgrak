<?php

namespace App\Exceptions;

use App\Models\JudgingCriteria;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Thrown by ScoreSheetService::submit() when not every scoreable criteria
 * has a Score row yet (fr-score-sheet-lifecycle.md Keputusan C — "status
 * submitted harus eksplisit", never guessed from form completeness).
 */
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
