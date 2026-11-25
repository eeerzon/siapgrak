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

    /**
     * @param  array<string, int|string>  $query  extra query, e.g. ['kategori' => 2] to reopen a category tab
     */
    protected function backToTab(CompetitionListing $competition, string $tab, string $message, array $query = []): RedirectResponse
    {
        return redirect()
            ->route('dashboard.content.competitions.judging.show', [$competition, 'tab' => $tab, ...$query])
            ->with('success', $message);
    }
}
