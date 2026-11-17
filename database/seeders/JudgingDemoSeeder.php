<?php

namespace Database\Seeders;

use App\Enums\CompetitionEntryStatus;
use App\Enums\EventStatus;
use App\Enums\OrganizationRole;
use App\Enums\OrganizationType;
use App\Enums\RegistrationStatus;
use App\Enums\ScoreSheetStatus;
use App\Enums\Status;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Judge;
use App\Models\JudgeAssignment;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\Score;
use App\Models\ScoreSheet;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fictional event with everything the judging flow needs: teams, entries, rounds,
 * criteria, judges with fixed access codes, assignments and a mix of score-sheet
 * states (empty, draft, submitted, locked). Run it explicitly:
 *   php artisan db:seed --class=JudgingDemoSeeder
 * Skips everything when the demo organization already exists.
 */
class JudgingDemoSeeder extends Seeder
{
    public const ORGANIZATION_SLUG = 'demo-panitia-lkbb-garuda';

    /** Fixed codes so testers can log in at /judge/access. Alphabet has no 0/O/1/I/L. */
    public const JUDGES = [
        ['Hendra Wijaya', 'JURYA234', 'Pelatih PBB 15 tahun, juri tingkat provinsi.'],
        ['Ratna Sari', 'JURYB345', 'Purna Paskibraka, juri variasi dan formasi.'],
        ['Dimas Pratama', 'JURYC456', 'Danton nasional 2019, menilai kepemimpinan danton.'],
    ];

    public function run(): void
    {
        if (Organization::where('slug', self::ORGANIZATION_SLUG)->exists()) {
            $this->command?->warn('Data demo penjurian sudah ada, dilewati.');

            return;
        }

        DB::transaction(function (): void {
            mt_srand(2026);

            $organization = $this->organization();
            $event = $this->event($organization);
            [$sma, $smp] = $this->categories($event);

            $smaEntries = $this->entries($organization, $event, $sma, [
                'SMAN Contoh 1 Jakarta', 'SMAN Contoh 2 Bandung', 'SMKN Contoh 3 Bekasi',
                'SMA Contoh Bina Bangsa', 'MAN Contoh 1 Bogor', 'SMAN Contoh 6 Depok',
            ]);
            $smpEntries = $this->entries($organization, $event, $smp, [
                'SMPN Contoh 1 Jakarta', 'SMPN Contoh 4 Tangerang', 'MTsN Contoh 2 Bekasi', 'SMP Contoh Tunas Muda',
            ]);

            $smaRound = $this->round($sma);
            $smpRound = $this->round($smp);

            [$hendra, $ratna, $dimas] = collect(self::JUDGES)->map(fn (array $judge) => $this->judge($organization, ...$judge))->all();

            // Whole-round assignments, plus one judge limited to a single criterion.
            $this->assign($smaRound, $hendra);
            $this->assign($smaRound, $ratna);
            $this->assign($smaRound, $dimas, $smaRound->criteria->firstWhere('name', 'Kepemimpinan Danton'));
            $this->assign($smpRound, $hendra);
            $this->assign($smpRound, $ratna);

            // A spread of states so every screen has something to show.
            foreach ($smaEntries->take(3) as $entry) {
                $this->sheet($smaRound, $entry, $hendra, ScoreSheetStatus::Submitted);
            }
            $this->sheet($smaRound, $smaEntries[3], $hendra, ScoreSheetStatus::Draft, partial: true);
            $this->sheet($smaRound, $smaEntries[0], $ratna, ScoreSheetStatus::Locked);
            $this->sheet($smaRound, $smaEntries[1], $ratna, ScoreSheetStatus::Submitted);
            $this->sheet($smaRound, $smaEntries[0], $dimas, ScoreSheetStatus::Submitted);
            $this->sheet($smpRound, $smpEntries[0], $ratna, ScoreSheetStatus::Draft, partial: true);
        });
    }

    private function organization(): Organization
    {
        $organization = (new Organization)->forceFill([
            'name' => 'Panitia Demo LKBB Garuda',
            'slug' => self::ORGANIZATION_SLUG,
            'type' => OrganizationType::Organizer,
            'description' => 'Organisasi contoh untuk mencoba alur penjurian.',
            'status' => Status::Active,
        ]);
        $organization->save();

        // Every platform admin joins: the first as owner (can reopen locked sheets), others as admin.
        User::where('is_platform_admin', true)->orderBy('id_user')->get()->each(function (User $user, int $index) use ($organization): void {
            $organization->users()->attach($user, [
                'role' => $index === 0 ? OrganizationRole::Owner : OrganizationRole::Admin,
                'status' => Status::Active,
            ]);
        });

        return $organization;
    }

    private function event(Organization $organization): Event
    {
        $event = (new Event)->forceFill([
            'organization_id' => $organization->id,
            'name' => 'LKBB Garuda Muda Cup (Demo)',
            'slug' => 'demo-lkbb-garuda-muda-cup',
            'short_description' => 'Event contoh untuk mencoba penjurian.',
            'start_date' => today(),
            'end_date' => today()->addDay(),
            'venue_name' => 'GOR Contoh Depok',
            'status' => EventStatus::Ongoing,
            'published_at' => now(),
            'created_by' => User::where('is_platform_admin', true)->orderBy('id_user')->value('id_user'),
        ]);
        $event->save();

        return $event;
    }

    /**
     * @return array{0: EventCategory, 1: EventCategory}
     */
    private function categories(Event $event): array
    {
        return collect([
            ['SMA/SMK/MA Sederajat', 'SMA', 1],
            ['SMP/MTs Sederajat', 'SMP', 2],
        ])->map(function (array $category) use ($event): EventCategory {
            [$name, $code, $order] = $category;
            $model = (new EventCategory)->forceFill([
                'event_id' => $event->id,
                'name' => $name,
                'code' => $code,
                'quota' => 40,
                'min_members' => 15,
                'max_members' => 25,
                'status' => 'active',
                'display_order' => $order,
            ]);
            $model->save();

            return $model;
        })->all();
    }

    /**
     * @param  list<string>  $teamNames
     * @return Collection<int, CompetitionEntry>
     */
    private function entries(Organization $organization, Event $event, EventCategory $category, array $teamNames)
    {
        return collect($teamNames)->values()->map(function (string $teamName, int $index) use ($organization, $event, $category): CompetitionEntry {
            $team = (new Team)->forceFill([
                'organization_id' => $organization->id,
                'name' => 'Paskibra '.$teamName,
                'slug' => 'demo-'.Str::slug($teamName),
                'status' => Status::Active,
            ]);
            $team->save();

            $registration = (new Registration)->forceFill([
                'event_id' => $event->id,
                'event_category_id' => $category->id,
                'team_id' => $team->id,
                'status' => RegistrationStatus::Approved,
                'submitted_at' => now()->subWeek(),
                'reviewed_at' => now()->subDays(5),
            ]);
            $registration->save();

            $entry = (new CompetitionEntry)->forceFill([
                'event_id' => $event->id,
                'event_category_id' => $category->id,
                'registration_id' => $registration->id,
                'team_id' => $team->id,
                'competition_number' => $category->code.'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'performance_order' => $index + 1,
                'status' => $index < 3 ? CompetitionEntryStatus::Finished : CompetitionEntryStatus::CheckedIn,
                'checked_in_at' => now()->subHours(3),
            ]);
            $entry->save();

            return $entry;
        });
    }

    private function round(EventCategory $category): JudgingRound
    {
        $round = (new JudgingRound)->forceFill([
            'event_category_id' => $category->id,
            'name' => 'Babak Final',
            'code' => 'FINAL',
            'weight' => 100,
            'status' => Status::Active,
            'display_order' => 1,
        ]);
        $round->save();

        foreach ([
            ['PBB Murni', 'Kerapian barisan, ketepatan aba-aba dan gerakan.', 40],
            ['Variasi', 'Kreativitas dan kekompakan gerakan variasi.', 25],
            ['Formasi', 'Bentuk formasi, perpindahan, dan kerapian.', 25],
            ['Kepemimpinan Danton', 'Suara, sikap, dan ketegasan danton.', 10],
        ] as $order => [$name, $description, $weight]) {
            (new JudgingCriteria)->forceFill([
                'judging_round_id' => $round->id,
                'name' => $name,
                'description' => $description,
                'weight' => $weight,
                'minimum_score' => 0,
                'maximum_score' => 100,
                'display_order' => $order + 1,
                'status' => Status::Active,
            ])->save();
        }

        return $round->load('criteria');
    }

    private function judge(Organization $organization, string $name, string $accessCode, string $bio): Judge
    {
        $judge = (new Judge)->forceFill([
            'organization_id' => $organization->id,
            'name' => $name,
            'bio' => $bio,
            'status' => Status::Active,
            'access_code' => $accessCode,
        ]);
        $judge->save();

        return $judge;
    }

    private function assign(JudgingRound $round, Judge $judge, ?JudgingCriteria $criterion = null): void
    {
        (new JudgeAssignment)->forceFill([
            'judging_round_id' => $round->id,
            'judge_id' => $judge->id,
            'judging_criteria_id' => $criterion?->id,
            'status' => Status::Active,
        ])->save();
    }

    /**
     * Scores every criterion this judge may score; a partial draft leaves the last one empty.
     */
    private function sheet(JudgingRound $round, CompetitionEntry $entry, Judge $judge, ScoreSheetStatus $status, bool $partial = false): void
    {
        $assignment = $round->assignments()->where('judge_id', $judge->id)->first();
        $criteria = $assignment->judging_criteria_id
            ? $round->criteria->where('id', $assignment->judging_criteria_id)
            : $round->criteria;

        if ($partial) {
            $criteria = $criteria->slice(0, -1);
        }

        $sheet = (new ScoreSheet)->forceFill([
            'judging_round_id' => $round->id,
            'competition_entry_id' => $entry->id,
            'judge_id' => $judge->id,
            'status' => $status,
            'submitted_at' => $status === ScoreSheetStatus::Draft ? null : now()->subHour(),
            'locked_at' => $status === ScoreSheetStatus::Locked ? now()->subMinutes(30) : null,
            'revision_count' => 0,
        ]);
        $sheet->save();

        foreach ($criteria as $criterion) {
            (new Score)->forceFill([
                'score_sheet_id' => $sheet->id,
                'judging_criteria_id' => $criterion->id,
                'score' => mt_rand(7000, 9500) / 100,
            ])->save();
        }
    }
}
