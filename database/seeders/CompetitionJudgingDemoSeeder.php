<?php

namespace Database\Seeders;

use App\Enums\ScoreSheetStatus;
use App\Enums\Status;
use App\Models\CompetitionCategory;
use App\Models\CompetitionJudge;
use App\Models\CompetitionJudging;
use App\Models\CompetitionListing;
use App\Models\CompetitionParticipant;
use App\Models\CompetitionRound;
use App\Services\CompetitionJudgingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for Info Lomba judging (/dashboard/content/competitions/{id}/judging and /juri).
 * Run it explicitly:
 *   php artisan db:seed --class=CompetitionJudgingDemoSeeder
 * A listing that already has categories is skipped, so re-running never overwrites.
 */
class CompetitionJudgingDemoSeeder extends Seeder
{
    /** Fixed codes for testing at /juri/masuk. Alphabet has no 0/O/1/I/L. */
    public const CODES = [
        'Hendra Wijaya' => 'GRDA2345',
        'Ratna Sari' => 'GRDB3456',
        'Dimas Pratama' => 'GRDC4567',
        'Juri Contoh Admin 2' => 'ADMN2345',
    ];

    private const CRITERIA = [
        ['PBB Murni', 'Kerapian barisan, ketepatan aba-aba dan gerakan.', 40],
        ['Variasi', 'Kreativitas dan kekompakan gerakan variasi.', 25],
        ['Formasi', 'Bentuk formasi, perpindahan, dan kerapian.', 25],
        ['Kepemimpinan Danton', 'Suara, sikap, dan ketegasan danton.', 10],
    ];

    public function run(): void
    {
        mt_srand(2026);

        DB::transaction(function (): void {
            $this->fullDemo();
            $this->smallDemoForSecondAdmin();
        });
    }

    private function fullDemo(): void
    {
        $listing = CompetitionListing::where('title', 'LKBB Garuda Muda Cup')->first();
        if (! $listing || $listing->categories()->exists()) {
            return;
        }

        [$hendra, $ratna, $dimas] = collect(['Hendra Wijaya', 'Ratna Sari', 'Dimas Pratama'])
            ->map(fn (string $name) => $this->judge($listing->id_user, $name, 'Juri contoh untuk demo penjurian.'))
            ->all();

        $sma = $this->category($listing, 'SMA/SMK/MA Sederajat', 1, 'SMA', [
            ['Paskibra SMAN Contoh 1', 'Jakarta'], ['Paskibra SMAN Contoh 2', 'Bandung'], ['Paskibra SMKN Contoh 3', 'Bekasi'],
            ['Paskibra SMA Contoh Bina Bangsa', 'Depok'], ['Paskibra MAN Contoh 1', 'Bogor'], ['Paskibra SMAN Contoh 6', 'Depok'],
        ]);
        $smp = $this->category($listing, 'SMP/MTs Sederajat', 2, 'SMP', [
            ['Paskibra SMPN Contoh 1', 'Jakarta'], ['Paskibra SMPN Contoh 4', 'Tangerang'],
            ['Paskibra MTsN Contoh 2', 'Bekasi'], ['Paskibra SMP Contoh Tunas Muda', 'Depok'],
        ]);

        $smaRound = $this->round($sma['category'], [$hendra, $ratna, $dimas]);
        $smpRound = $this->round($smp['category'], [$hendra, $ratna]);

        // A spread of states: sent, locked, partial draft, untouched.
        foreach ($sma['participants']->take(3) as $participant) {
            $this->sheet($smaRound, $participant, $hendra, ScoreSheetStatus::Submitted);
            $this->sheet($smaRound, $participant, $ratna, ScoreSheetStatus::Locked);
        }
        $this->sheet($smaRound, $sma['participants'][0], $dimas, ScoreSheetStatus::Submitted);
        $this->sheet($smaRound, $sma['participants'][1], $dimas, ScoreSheetStatus::Submitted);
        $this->sheet($smaRound, $sma['participants'][2], $dimas, ScoreSheetStatus::Submitted);
        $this->sheet($smaRound, $sma['participants'][3], $hendra, ScoreSheetStatus::Draft, partial: true);
        $this->sheet($smpRound, $smp['participants'][0], $ratna, ScoreSheetStatus::Draft, partial: true);

        // First three SMA participants are complete, so the ranking has something to show.
        app(CompetitionJudgingService::class)->calculateResults($smaRound);
    }

    private function smallDemoForSecondAdmin(): void
    {
        $listing = CompetitionListing::where('title', 'Lomba Contoh Milik Admin 2')->first();
        if (! $listing || $listing->categories()->exists()) {
            return;
        }

        $judge = $this->judge($listing->id_user, 'Juri Contoh Admin 2', 'Juri contoh milik admin kedua.');
        $category = $this->category($listing, 'Umum', 1, 'U', [
            ['Paskibra Contoh Satu', 'Bekasi'], ['Paskibra Contoh Dua', 'Bekasi'], ['Paskibra Contoh Tiga', 'Karawang'],
        ]);
        $this->round($category['category'], [$judge]);
    }

    private function judge(?int $owner, string $name, string $bio): CompetitionJudge
    {
        $judge = (new CompetitionJudge)->forceFill([
            'id_user' => $owner,
            'name' => $name,
            'bio' => $bio,
            'status' => Status::Active,
            'access_code' => self::CODES[$name],
        ]);
        $judge->save();

        return $judge;
    }

    /**
     * @param  list<array{0: string, 1: string}>  $teams
     * @return array{category: CompetitionCategory, participants: Collection<int, CompetitionParticipant>}
     */
    private function category(CompetitionListing $listing, string $name, int $order, string $prefix, array $teams): array
    {
        $category = $listing->categories()->create(['name' => $name, 'display_order' => $order]);

        $participants = collect($teams)->values()->map(fn (array $team, int $index) => $listing->participants()->create([
            'competition_category_id' => $category->id,
            'name' => $team[0],
            'institution' => $team[1],
            'competition_number' => $prefix.'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
            'performance_order' => $index + 1,
        ]));

        return ['category' => $category, 'participants' => $participants];
    }

    /**
     * @param  list<CompetitionJudge>  $judges
     */
    private function round($category, array $judges): CompetitionRound
    {
        $round = CompetitionRound::create(['competition_category_id' => $category->id, 'name' => 'Babak Final', 'display_order' => 1]);

        foreach (self::CRITERIA as $order => [$name, $description, $weight]) {
            $round->criteria()->create([
                'name' => $name,
                'description' => $description,
                'weight' => $weight,
                'minimum_score' => 0,
                'maximum_score' => 100,
                'display_order' => $order + 1,
            ]);
        }

        $round->judges()->attach(collect($judges)->pluck('id'));

        return $round->load('criteria');
    }

    private function sheet(CompetitionRound $round, CompetitionParticipant $participant, CompetitionJudge $judge, ScoreSheetStatus $status, bool $partial = false): void
    {
        $judging = (new CompetitionJudging)->forceFill([
            'competition_listing_id' => $participant->competition_listing_id,
            'competition_round_id' => $round->id,
            'competition_participant_id' => $participant->id,
            'competition_judge_id' => $judge->id,
            'status' => $status,
            'submitted_at' => $status === ScoreSheetStatus::Draft ? null : now()->subHour(),
            'locked_at' => $status === ScoreSheetStatus::Locked ? now()->subMinutes(20) : null,
        ]);
        $judging->save();

        $criteria = $partial ? $round->criteria->slice(0, -1) : $round->criteria;
        foreach ($criteria as $criterion) {
            $judging->scores()->create(['competition_criterion_id' => $criterion->id, 'score' => mt_rand(7000, 9500) / 100]);
        }
    }
}
