<?php

namespace Database\Seeders;

use App\Enums\ScoreSheetStatus;
use App\Models\CompetitionCategory;
use App\Models\CompetitionJudging;
use App\Models\CompetitionListing;
use App\Models\CompetitionRound;
use App\Services\CompetitionJudgingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Gives every category of "LKBB Garuda Muda Cup" three rounds (Grup → Penyisihan → Final)
 * to check the public results page with several rounds. Needs CompetitionJudgingDemoSeeder
 * (and preferably CompetitionManyParticipantsDemoSeeder) first.
 *   php artisan db:seed --class=CompetitionMultiRoundDemoSeeder
 * Rounds that already exist are left alone, so re-running changes nothing.
 */
class CompetitionMultiRoundDemoSeeder extends Seeder
{
    private const CRITERIA = [
        ['PBB Murni', 'Kerapian barisan, ketepatan aba-aba dan gerakan.', 40],
        ['Variasi', 'Kreativitas dan kekompakan gerakan variasi.', 25],
        ['Formasi', 'Bentuk formasi, perpindahan, dan kerapian.', 25],
        ['Kepemimpinan Danton', 'Suara, sikap, dan ketegasan danton.', 10],
    ];

    /** Round name => how many participants (from the top of the list) get full scores; null = all. */
    private const PLAN = [
        'SMA/SMK/MA Sederajat' => ['Babak Grup' => null, 'Babak Penyisihan' => 4],
        'SMP/MTs Sederajat' => ['Babak Grup' => null, 'Babak Penyisihan' => 16],
    ];

    public function run(): void
    {
        mt_srand(3030);

        $listing = CompetitionListing::where('title', 'LKBB Garuda Muda Cup')->first();
        if (! $listing) {
            $this->command?->warn('Jalankan CompetitionJudgingDemoSeeder dulu.');

            return;
        }

        $service = app(CompetitionJudgingService::class);

        DB::transaction(function () use ($listing, $service): void {
            foreach (self::PLAN as $categoryName => $rounds) {
                $category = $listing->categories()->where('name', $categoryName)->first();
                $final = $category?->rounds()->where('name', 'Babak Final')->first();
                if (! $category || ! $final) {
                    continue;
                }

                $order = 1;
                foreach ($rounds as $name => $scoredCount) {
                    if (! $category->rounds()->where('name', $name)->exists()) {
                        $round = $this->round($category, $name, $order, $final->judges()->pluck('competition_judges.id')->all());
                        $this->score($round, $category, $scoredCount);
                        $service->publishResults($round);
                    }
                    $order++;
                }

                // Final goes last; its scores and publish state are not touched. As a later round it only
                // has the teams moved on to it: here, the ones that already have a sheet there.
                $final->forceFill(['display_order' => $order])->save();
                $final->participants()->syncWithoutDetaching($final->judgings()->distinct()->pluck('competition_participant_id')->all());
            }
        });
    }

    /**
     * @param  list<int>  $judgeIds
     */
    private function round(CompetitionCategory $category, string $name, int $order, array $judgeIds): CompetitionRound
    {
        $round = CompetitionRound::create(['competition_category_id' => $category->id, 'name' => $name, 'display_order' => $order]);

        foreach (self::CRITERIA as $index => [$criterion, $description, $weight]) {
            $round->criteria()->create([
                'name' => $criterion,
                'description' => $description,
                'weight' => $weight,
                'minimum_score' => 0,
                'maximum_score' => 100,
                'display_order' => $index + 1,
            ]);
        }

        $round->judges()->attach($judgeIds);

        return $round->load('criteria', 'judges');
    }

    /**
     * Every assigned judge sends a complete sheet for the first $count participants.
     */
    private function score(CompetitionRound $round, CompetitionCategory $category, ?int $count): void
    {
        $participants = $category->participants()->get();
        $participants = $count === null ? $participants : $participants->take($count);

        // Later rounds only have the teams moved on to them (the first round has everyone).
        if ($count !== null) {
            $round->participants()->syncWithoutDetaching($participants->pluck('id')->all());
        }

        foreach ($participants as $participant) {
            // A per-team level keeps the judges roughly in agreement, like a real panel.
            $level = mt_rand(7200, 9000) / 100;

            foreach ($round->judges as $judge) {
                $judging = (new CompetitionJudging)->forceFill([
                    'competition_listing_id' => $participant->competition_listing_id,
                    'competition_round_id' => $round->id,
                    'competition_participant_id' => $participant->id,
                    'competition_judge_id' => $judge->id,
                    'status' => ScoreSheetStatus::Submitted,
                    'submitted_at' => now()->subDays(2),
                ]);
                $judging->save();

                foreach ($round->criteria as $criterion) {
                    $judging->scores()->create([
                        'competition_criterion_id' => $criterion->id,
                        'score' => min(100, max(0, round($level + mt_rand(-500, 500) / 100, 2))),
                    ]);
                }
            }
        }
    }
}
