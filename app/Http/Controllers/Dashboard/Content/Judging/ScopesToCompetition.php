<?php

namespace App\Http\Controllers\Dashboard\Content\Judging;

use App\Models\CompetitionListing;
use Illuminate\Http\RedirectResponse;

/**
 * Shared by the Info Lomba judging controllers: ownership check and the
 * redirect back to the right workspace tab.
 */
trait ScopesToCompetition
{
    /**
     * Nested records must belong to the listing in the URL, otherwise 404.
     */
    protected function ensureBelongs(CompetitionListing $competition, int $listingId): void
    {
        abort_unless($listingId === $competition->id, 404);
    }

    protected function backToTab(CompetitionListing $competition, string $tab, string $message): RedirectResponse
    {
        return redirect()
            ->route('dashboard.content.competitions.judging.show', [$competition, 'tab' => $tab])
            ->with('success', $message);
    }
}
