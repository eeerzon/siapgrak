<?php

namespace Database\Seeders;

use App\Enums\ScoreSheetStatus;
use App\Models\CompetitionCategory;
use App\Models\CompetitionJudge;
use App\Models\CompetitionJudging;
use App\Models\CompetitionListing;
use App\Models\CompetitionParticipant;
use App\Models\CompetitionRound;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fills the SMP category of "LKBB Garuda Muda Cup" up to 44 participants, to check how
 * the "Belum diperingkat" list behaves with a long list. Needs CompetitionJudgingDemoSeeder first.
 *   php artisan db:seed --class=CompetitionManyParticipantsDemoSeeder
 * Only adds what is missing, so re-running changes nothing.
 */
class CompetitionManyParticipantsDemoSeeder extends Seeder
{
    private const TARGET = 44;

    private const SCHOOLS = ['SMPN', 'MTsN', 'SMP Islam', 'SMP Kristen', 'SMP Taruna', 'SMP Bina Insani', 'MTs Al-Hikmah', "SMP Ma'arif"];

    private const CITIES = ['Jakarta', 'Bekasi', 'Depok', 'Bogor', 'Tangerang', 'Bandung', 'Karawang', 'Cirebon', 'Serang', 'Sukabumi'];

    public function run(): void
    {
        mt_srand(4444);

        $listing = CompetitionListing::where('title', 'LKBB Garuda Muda Cup')->first();
        $category = $listing?->categories()->where('name', 'SMP/MTs Sederajat')->first();
        $round = $category ? CompetitionRound::where('competition_category_id', $category->id)->with('criteria')->first() : null;

        if (! $listing || ! $category || ! $round) {
            $this->command?->warn('Jalankan CompetitionJudgingDemoSeeder dulu.');

            return;
        }

        DB::transaction(function () use ($listing, $category, $round): void {
            $this->fillParticipants($listing, $category);

            $hendra = CompetitionJudge::where('access_code', CompetitionJudgingDemoSeeder::CODES['Hendra Wijaya'])->first();
            $ratna = CompetitionJudge::where('access_code', CompetitionJudgingDemoSeeder::CODES['Ratna Sari'])->first();
            $byNumber = $category->participants()->get()->keyBy('competition_number');

            // A spread of states: Ratna sent 10, Hendra sent 4 of the same (those 4 become complete) and drafted 3.
            foreach (range(5, 14) as $n) {
                $this->sheet($round, $byNumber["SMP-{$this->pad($n)}"], $ratna, ScoreSheetStatus::Submitted);
            }
            foreach (range(5, 8) as $n) {
                $this->sheet($round, $byNumber["SMP-{$this->pad($n)}"], $hendra, ScoreSheetStatus::Submitted);
            }
            foreach (range(9, 11) as $n) {
                $this->sheet($round, $byNumber["SMP-{$this->pad($n)}"], $hendra, ScoreSheetStatus::Draft);
            }
        });
    }

    private function fillParticipants(CompetitionListing $listing, CompetitionCategory $category): void
    {
        $existing = $category->participants()->pluck('competition_number')->all();

        for ($n = 1; $n <= self::TARGET; $n++) {
            $number = 'SMP-'.$this->pad($n);
            if (in_array($number, $existing, true)) {
                continue;
            }

            $listing->participants()->create([
                'competition_category_id' => $category->id,
                'name' => 'Paskibra '.self::SCHOOLS[$n % count(self::SCHOOLS)].' '.(($n * 7) % 40 + 1),
                'institution' => self::CITIES[$n % count(self::CITIES)],
                'competition_number' => $number,
                'performance_order' => $n,
            ]);
        }
    }

    private function sheet(CompetitionRound $round, CompetitionParticipant $participant, ?CompetitionJudge $judge, ScoreSheetStatus $status): void
    {
        if (! $judge || CompetitionJudging::where(['competition_round_id' => $round->id, 'competition_participant_id' => $participant->id, 'competition_judge_id' => $judge->id])->exists()) {
            return;
        }

        $judging = (new CompetitionJudging)->forceFill([
            'competition_listing_id' => $participant->competition_listing_id,
            'competition_round_id' => $round->id,
            'competition_participant_id' => $participant->id,
            'competition_judge_id' => $judge->id,
            'status' => $status,
            'submitted_at' => $status === ScoreSheetStatus::Draft ? null : now()->subMinutes(30),
        ]);
        $judging->save();

        // Drafts are half filled, the way a judge leaves them mid-way.
        $criteria = $status === ScoreSheetStatus::Draft ? $round->criteria->take(2) : $round->criteria;
        foreach ($criteria as $criterion) {
            $judging->scores()->create(['competition_criterion_id' => $criterion->id, 'score' => mt_rand(7000, 9500) / 100]);
        }
    }

    private function pad(int $n): string
    {
        return str_pad((string) $n, 2, '0', STR_PAD_LEFT);
    }
}
