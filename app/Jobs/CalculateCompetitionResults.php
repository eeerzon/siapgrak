<?php

namespace App\Jobs;

use App\Models\JudgingRound;
use App\Services\ResultService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Dispatched via ->dispatchSync() from ResultController, not ->dispatch()
 * (fr-awards-finalization.md Keputusan J) — QUEUE_CONNECTION=database here
 * with no queue worker ever run/documented for this project. A real
 * dispatch() would silently sit in the `jobs` table forever. Swapping to
 * dispatch() later, once a worker exists, needs no other code change.
 */
class CalculateCompetitionResults implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(protected JudgingRound $round) {}

    public function handle(ResultService $results): void
    {
        $results->calculateForRound($this->round);
    }
}
