<?php

namespace App\Http\Controllers\Dashboard\Content\Judging;

use App\Http\Controllers\Controller;
use App\Models\CompetitionJudging;
use App\Models\CompetitionListing;
use App\Models\CompetitionRound;
use App\Services\CompetitionJudgingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin side of score sheets: lock, reopen (reason required, audited) and ranking.
 * Admins can see every judge's sheet; judges themselves never see each other's.
 */
class ScoringController extends Controller
{
    use ScopesToCompetition;

    public function __construct(private CompetitionJudgingService $judging) {}

    public function lock(CompetitionListing $competition, CompetitionJudging $judging): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $judging->competition_listing_id);

        $this->judging->lock($judging);

        return $this->backToTab($competition, 'lembar', 'Lembar nilai dikunci.', ['kategori' => $judging->round->competition_category_id]);
    }

    public function reopen(Request $request, CompetitionListing $competition, CompetitionJudging $judging): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $judging->competition_listing_id);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']], [], ['reason' => 'alasan']);

        $this->judging->reopen($judging, $request->user(), $data['reason']);

        return $this->backToTab($competition, 'lembar', 'Lembar nilai dibuka ulang. Juri bisa memperbaiki nilainya.', ['kategori' => $judging->round->competition_category_id]);
    }

    public function calculate(CompetitionListing $competition, CompetitionRound $round): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $round->category->competition_listing_id);

        $count = $this->judging->calculateResults($round);

        return $this->backToTab($competition, 'hasil', "Hasil {$round->category->name} · {$round->name} dihitung untuk {$count} peserta.", ['kategori' => $round->competition_category_id]);
    }

    public function publish(CompetitionListing $competition, CompetitionRound $round): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $round->category->competition_listing_id);

        $count = $this->judging->publishResults($round);

        return $this->backToTab($competition, 'hasil', "Peringkat {$round->category->name} · {$round->name} dihitung ulang untuk {$count} peserta dan sudah tampil di halaman publik.", ['kategori' => $round->competition_category_id]);
    }

    public function unpublish(CompetitionListing $competition, CompetitionRound $round): RedirectResponse
    {
        $this->authorize('manage', $competition);
        $this->ensureBelongs($competition, $round->category->competition_listing_id);

        $cleared = $this->judging->unpublishResults($round);
        $note = $cleared === [] ? '' : ' Peserta '.implode(', ', $cleared).' dikosongkan karena dipilih dari peringkat ini; pilih ulang setelah hasilnya dipublikasikan lagi.';

        return $this->backToTab($competition, 'hasil', "Hasil {$round->category->name} · {$round->name} ditarik dari halaman publik.{$note}", ['kategori' => $round->competition_category_id]);
    }
}
