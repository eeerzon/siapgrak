<?php

namespace App\Services;

use App\Models\CompetitionEntry;
use App\Models\EventCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CompetitionNumberService
{
    public function assignManual(CompetitionEntry $entry, ?string $number): CompetitionEntry
    {
        $entry->competition_number = $number;
        $entry->save();

        return $entry;
    }

    /**
     * Sequentially numbers every entry in $category that doesn't have a
     * number yet, in entry-creation order. Wrapped in a transaction with a
     * row lock so two concurrent "Generate" clicks for the same category can
     * never hand out the same number (fr-competition-entry.md Keputusan D /
     * srs/nfr.md §Data Integrity race-condition rule) — the second request
     * blocks until the first commits, then sees the rows already numbered.
     * A start value that collides with an already-assigned number rolls the
     * whole batch back (unique constraint) — surfaced by the controller.
     *
     * @return Collection<int, CompetitionEntry>
     */
    public function generateSequential(EventCategory $category, string $prefix, int $start, int $digits): Collection
    {
        return DB::transaction(function () use ($category, $prefix, $start, $digits) {
            $entries = CompetitionEntry::query()
                ->where('event_category_id', $category->id)
                ->whereNull('competition_number')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $next = $start;

            foreach ($entries as $entry) {
                $entry->competition_number = $prefix.str_pad((string) $next, $digits, '0', STR_PAD_LEFT);
                $entry->save();
                $next++;
            }

            return $entries;
        });
    }
}
