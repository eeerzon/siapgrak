<?php

namespace App\Http\Controllers\Dashboard\Content\Judging;

use App\Http\Controllers\Controller;
use App\Models\CompetitionJudge;
use App\Models\CompetitionListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public const TABS = ['peserta' => 'Kategori & Peserta', 'penjurian' => 'Babak & Juri', 'lembar' => 'Lembar Nilai', 'hasil' => 'Hasil'];

    public function show(Request $request, CompetitionListing $competition): View
    {
        $this->authorize('manage', $competition);

        $tab = array_key_exists((string) $this->queryText($request, 'tab'), self::TABS) ? $this->queryText($request, 'tab') : 'peserta';

        $competition->load([
            'categories.participants',
            'categories.rounds.criteria',
            'categories.rounds.judges',
            'categories.rounds.judgings' => fn ($query) => $query
                ->with(['participant', 'judge', 'scores:id,competition_judging_id,competition_criterion_id'])
                ->withCount('scores')
                ->orderBy('competition_participant_id'),
            'categories.rounds.results.participant',
        ]);

        $judges = CompetitionJudge::ownedBy($request->user())->orderBy('name')->get();

        return view('dashboard.content.competitions.judging', compact('competition', 'tab', 'judges'));
    }
}
