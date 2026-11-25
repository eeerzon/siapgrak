<?php

namespace App\Http\Controllers\Site;

use App\Enums\ScoreSheetStatus;
use App\Http\Controllers\Controller;
use App\Models\CompetitionJudging;
use App\Models\CompetitionListing;
use App\Models\CompetitionRound;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

/**
 * Public ranking of an Info Lomba (no login). A round shows scores only after the
 * admin published it; until then the page says judging is still in progress.
 * Judges are anonymous ("Juri 1", "Juri 2", ... in assignment order).
 * /hasil shows every category; /hasil/{category} shows one, so each category can be shared on its own.
 * Each category shows ONE round at a time: /hasil/{category}/{round}, or by default the latest
 * published round (usually the final), so a final is never buried under a long group stage.
 */
class CompetitionResultController extends Controller
{
    public function show(string $slug, ?string $category = null, ?string $round = null): View
    {
        $competition = CompetitionListing::published()
            ->where('slug', $slug)
            ->with(['categories.rounds' => fn ($query) => $query->with(['criteria', 'results.participant'])])
            ->firstOrFail();

        // Only categories that have a round can have results; the others are left out of the page and the switcher.
        $categories = $competition->categories->filter(fn ($item) => $item->rounds->isNotEmpty())->values();
        $activeCategory = $category === null ? null : $categories->firstWhere('id', (int) $category);
        abort_if($categories->isEmpty() || ($category !== null && ! $activeCategory), 404);

        $shownCategories = $activeCategory ? collect([$activeCategory]) : $categories;
        $requestedRound = $round === null ? null : $activeCategory?->rounds->firstWhere('id', (int) $round);
        abort_if($round !== null && ! $requestedRound, 404);

        // One section per shown category: its rounds (for the switcher) and the round on display.
        $sections = $shownCategories->map(fn ($item) => [
            'category' => $item,
            'rounds' => $item->rounds,
            'round' => $requestedRound && $item->is($activeCategory)
                ? $requestedRound
                : ($item->rounds->filter(fn (CompetitionRound $candidate) => $candidate->isPublished())->last() ?? $item->rounds->first()),
        ])->values();

        $published = $sections->pluck('round')->filter(fn (CompetitionRound $item) => $item->isPublished());

        // Only sent/locked sheets count, exactly like the ranking itself.
        $sheets = CompetitionJudging::query()
            ->whereIn('competition_round_id', $published->pluck('id'))
            ->whereIn('status', [ScoreSheetStatus::Submitted, ScoreSheetStatus::Locked])
            ->with('scores')
            ->get()
            ->groupBy(['competition_round_id', 'competition_participant_id']);

        $details = $published->mapWithKeys(fn (CompetitionRound $item) => [
            $item->id => $this->roundDetail($item, $sheets->get($item->id, collect())),
        ]);

        return view('site.competitions.results', compact('competition', 'details', 'categories', 'activeCategory', 'sections'));
    }

    /**
     * @param  Collection<int, Collection<int, CompetitionJudging>>  $sheetsByParticipant
     * @return array{judges: list<string>, rows: array<int, list<array{label: string, total: float, scores: array<int, float>}>>}
     */
    private function roundDetail(CompetitionRound $round, Collection $sheetsByParticipant): array
    {
        $judgeIds = $round->judges()->reorder()->orderBy('competition_judge_assignments.id')->pluck('competition_judges.id');
        $label = fn (int $index) => $judgeIds->count() === 1 ? 'Juri' : 'Juri '.($index + 1);
        $weights = $round->criteria->mapWithKeys(fn ($criterion) => [$criterion->id => (float) $criterion->weight / 100]);

        $rows = $sheetsByParticipant->map(fn (Collection $sheets) => $judgeIds->values()->map(function (int $judgeId, int $index) use ($sheets, $label, $weights) {
            $scores = $sheets->firstWhere('competition_judge_id', $judgeId)?->scores
                ->mapWithKeys(fn ($score) => [$score->competition_criterion_id => (float) $score->score]) ?? collect();

            return [
                'label' => $label($index),
                'total' => round($scores->map(fn (float $score, int $criterionId) => $score * ($weights[$criterionId] ?? 0))->sum(), 3),
                'scores' => $scores->all(),
            ];
        })->all());

        return ['judges' => $judgeIds->keys()->map($label)->all(), 'rows' => $rows->all()];
    }
}
