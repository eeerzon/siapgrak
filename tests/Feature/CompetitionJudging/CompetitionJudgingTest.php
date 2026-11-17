<?php

namespace Tests\Feature\CompetitionJudging;

use App\Enums\ScoreSheetStatus;
use App\Models\CompetitionCategory;
use App\Models\CompetitionJudge;
use App\Models\CompetitionJudging;
use App\Models\CompetitionListing;
use App\Models\CompetitionParticipant;
use App\Models\CompetitionRound;
use App\Models\User;
use App\Services\CompetitionJudgingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionJudgingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private CompetitionListing $listing;

    private CompetitionCategory $category;

    private CompetitionRound $round;

    private CompetitionJudge $judge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_platform_admin' => true]);
        $this->listing = CompetitionListing::create(['title' => 'LKBB Uji', 'start_date' => '2030-01-01', 'status' => 'draft', 'id_user' => $this->admin->id_user]);
        $this->category = $this->listing->categories()->create(['name' => 'SMA']);
        $this->round = CompetitionRound::create(['competition_category_id' => $this->category->id, 'name' => 'Final']);
        $this->round->criteria()->create(['name' => 'PBB', 'weight' => 60, 'minimum_score' => 0, 'maximum_score' => 100]);
        $this->round->criteria()->create(['name' => 'Formasi', 'weight' => 40, 'minimum_score' => 0, 'maximum_score' => 100]);
        $this->judge = CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Juri Satu', 'status' => 'active']);
        $this->round->judges()->attach($this->judge);
    }

    private function participant(string $number): CompetitionParticipant
    {
        return $this->listing->participants()->create(['competition_category_id' => $this->category->id, 'name' => "Tim {$number}", 'competition_number' => $number]);
    }

    private function judgeSession(?CompetitionJudge $judge = null): static
    {
        return $this->withSession(['competition_judge_id' => ($judge ?? $this->judge)->id]);
    }

    /**
     * @param  array<int, float>  $scores  keyed by criterion position (0 = PBB, 1 = Formasi)
     */
    private function sentSheet(CompetitionParticipant $participant, CompetitionJudge $judge, array $scores, ScoreSheetStatus $status = ScoreSheetStatus::Submitted): CompetitionJudging
    {
        $judging = (new CompetitionJudging)->forceFill([
            'competition_listing_id' => $this->listing->id,
            'competition_round_id' => $this->round->id,
            'competition_participant_id' => $participant->id,
            'competition_judge_id' => $judge->id,
            'status' => $status,
        ]);
        $judging->save();

        foreach ($scores as $position => $score) {
            $judging->scores()->create(['competition_criterion_id' => $this->round->criteria[$position]->id, 'score' => $score]);
        }

        return $judging;
    }

    public function test_only_the_listing_owner_can_open_and_change_its_judging(): void
    {
        $other = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($this->admin)->get(route('dashboard.content.competitions.judging.show', $this->listing))->assertOk()->assertSee('Penjurian · LKBB Uji');
        $this->actingAs($other)->get(route('dashboard.content.competitions.judging.show', $this->listing))->assertForbidden();
        $this->actingAs($other)->post(route('dashboard.content.competitions.judging.categories.store', $this->listing), ['name' => 'SMP'])->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('dashboard.content.competitions.judging.show', $this->listing))->assertForbidden();
    }

    public function test_admin_sets_up_categories_participants_rounds_criteria_and_own_judges_only(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('dashboard.content.competitions.judging.categories.store', $this->listing), ['name' => 'SMP'])->assertSessionHasNoErrors();
        $smp = $this->listing->categories()->where('name', 'SMP')->firstOrFail();

        $this->post(route('dashboard.content.competitions.judging.participants.store', $this->listing), [
            'competition_category_id' => $smp->id, 'name' => 'Paskibra Uji', 'competition_number' => 'SMP-01',
        ])->assertSessionHasNoErrors();
        $this->post(route('dashboard.content.competitions.judging.participants.store', $this->listing), [
            'competition_category_id' => $smp->id, 'name' => 'Duplikat', 'competition_number' => 'SMP-01',
        ])->assertSessionHasErrors('competition_number');

        $this->post(route('dashboard.content.competitions.judging.rounds.store', $this->listing), ['competition_category_id' => $smp->id, 'name' => 'Final SMP'])->assertSessionHasNoErrors();
        $round = CompetitionRound::where('name', 'Final SMP')->firstOrFail();

        $this->post(route('dashboard.content.competitions.judging.criteria.store', [$this->listing, $round]), [
            'name' => 'Danton', 'weight' => 100, 'minimum_score' => 0, 'maximum_score' => 50,
        ])->assertSessionHasNoErrors();
        $this->post(route('dashboard.content.competitions.judging.criteria.store', [$this->listing, $round]), [
            'name' => 'Salah', 'minimum_score' => 50, 'maximum_score' => 10,
        ])->assertSessionHasErrors('maximum_score');

        $this->post(route('dashboard.content.competitions.judging.judges.assign', [$this->listing, $round]), ['competition_judge_id' => $this->judge->id])->assertSessionHasNoErrors();

        $foreignJudge = CompetitionJudge::create(['id_user' => User::factory()->create(['is_platform_admin' => true])->id_user, 'name' => 'Juri Orang Lain', 'status' => 'active']);
        $this->post(route('dashboard.content.competitions.judging.judges.assign', [$this->listing, $round]), ['competition_judge_id' => $foreignJudge->id])->assertSessionHasErrors('competition_judge_id');

        $this->assertSame([$this->judge->id], $round->judges()->pluck('competition_judges.id')->all());
    }

    public function test_records_with_scores_cannot_be_deleted(): void
    {
        $participant = $this->participant('A1');
        $this->sentSheet($participant, $this->judge, [80, 70]);
        $this->actingAs($this->admin);

        $this->delete(route('dashboard.content.competitions.judging.participants.destroy', [$this->listing, $participant]))->assertSessionHasErrors('participant');
        $this->delete(route('dashboard.content.competitions.judging.criteria.destroy', [$this->listing, $this->round->criteria[0]]))->assertSessionHasErrors('criterion');
        $this->delete(route('dashboard.content.competitions.judging.rounds.destroy', [$this->listing, $this->round]))->assertSessionHasErrors('round');
        $this->delete(route('dashboard.content.competitions.destroy', $this->listing))->assertSessionHas('error');

        $this->assertModelExists($participant);
        $this->assertModelExists($this->listing);
    }

    public function test_judge_logs_in_with_access_code_and_inactive_judges_are_refused(): void
    {
        $this->post(route('juri.access.store'), ['code' => strtolower($this->judge->access_code)])->assertRedirect(route('juri.dashboard'));
        $this->assertSame($this->judge->id, session('competition_judge_id'));

        $inactive = CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Nonaktif', 'status' => 'inactive']);
        $this->post(route('juri.logout'));
        $this->post(route('juri.access.store'), ['code' => $inactive->access_code])->assertSessionHasErrors('code');

        $this->get(route('juri.dashboard'))->assertRedirect(route('juri.access.create'));
    }

    public function test_judge_only_reaches_assigned_rounds_and_viewing_creates_no_sheet(): void
    {
        $participant = $this->participant('A1');
        $otherRound = CompetitionRound::create(['competition_category_id' => $this->category->id, 'name' => 'Semifinal']);

        $this->judgeSession()->get(route('juri.dashboard'))->assertOk()->assertSee('SMA · Final')->assertDontSee('Semifinal');
        $this->judgeSession()->get(route('juri.round', $otherRound))->assertForbidden();

        $this->judgeSession()->get(route('juri.score.show', [$this->round, $participant]))->assertOk()->assertSee('Kirim Skor Final');
        $this->assertSame(0, CompetitionJudging::count());
    }

    public function test_judge_saves_a_draft_then_submits_only_when_complete_and_cannot_edit_after(): void
    {
        $participant = $this->participant('A1');
        [$pbb, $formasi] = $this->round->criteria;

        $this->judgeSession()->post(route('juri.score.save', [$this->round, $participant]), ['scores' => [$pbb->id => ['score' => 150]]])
            ->assertSessionHasErrors("scores.{$pbb->id}.score");

        $this->judgeSession()->post(route('juri.score.save', [$this->round, $participant]), ['scores' => [$pbb->id => ['score' => 85.5]]])->assertSessionHasNoErrors();
        $this->judgeSession()->post(route('juri.score.submit', [$this->round, $participant]))->assertSessionHasErrors('submit');

        $this->judgeSession()->post(route('juri.score.save', [$this->round, $participant]), ['scores' => [$formasi->id => ['score' => 70]]])->assertSessionHasNoErrors();
        $this->judgeSession()->post(route('juri.score.submit', [$this->round, $participant]))->assertSessionHasNoErrors();

        $judging = CompetitionJudging::firstOrFail();
        $this->assertSame(ScoreSheetStatus::Submitted, $judging->status);
        $this->assertSame($this->listing->id, $judging->competition_listing_id);

        $this->judgeSession()->post(route('juri.score.save', [$this->round, $participant]), ['scores' => [$pbb->id => ['score' => 99]]])->assertSessionHasErrors('submit');
        $this->assertEquals(85.5, (float) $judging->scores()->where('competition_criterion_id', $pbb->id)->value('score'));
        $this->judgeSession()->get(route('juri.score.show', [$this->round, $participant]))->assertSee('disabled="disabled"', false)->assertDontSee('Kirim Skor Final');
    }

    public function test_judges_never_see_another_judges_scores(): void
    {
        $participant = $this->participant('A1');
        $second = CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Juri Dua', 'status' => 'active']);
        $this->round->judges()->attach($second);
        $this->sentSheet($participant, $this->judge, [87.25, 66.75]);

        $this->judgeSession($second)->get(route('juri.score.show', [$this->round, $participant]))
            ->assertOk()
            ->assertDontSee('87.25')
            ->assertDontSee('87,25');
        $this->judgeSession($second)->get(route('juri.round', $this->round))->assertSee('Belum dinilai')->assertDontSee('Terkirim');
    }

    public function test_admin_locks_and_reopens_with_a_reason_that_is_recorded(): void
    {
        $participant = $this->participant('A1');
        $judging = $this->sentSheet($participant, $this->judge, [80, 70]);
        $this->actingAs($this->admin);

        $this->post(route('dashboard.content.competitions.judging.judgings.lock', [$this->listing, $judging]))->assertSessionHasNoErrors();
        $this->assertSame(ScoreSheetStatus::Locked, $judging->fresh()->status);

        $this->post(route('dashboard.content.competitions.judging.judgings.reopen', [$this->listing, $judging]), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->post(route('dashboard.content.competitions.judging.judgings.reopen', [$this->listing, $judging]), ['reason' => 'Salah ketik nilai PBB'])->assertSessionHasNoErrors();

        $judging->refresh();
        $this->assertSame(ScoreSheetStatus::Draft, $judging->status);
        $this->assertSame(1, $judging->revision_count);
        $this->assertSame(2, $judging->scores()->count());
        $this->assertDatabaseHas('competition_judging_revisions', [
            'competition_judging_id' => $judging->id, 'id_user' => $this->admin->id_user, 'previous_status' => 'locked', 'reason' => 'Salah ketik nilai PBB',
        ]);
    }

    public function test_results_average_judges_apply_weights_share_ties_and_skip_incomplete_or_draft(): void
    {
        $second = CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Juri Dua', 'status' => 'active']);
        $this->round->judges()->attach($second);
        [$a, $b, $c, $d, $e] = collect(['A', 'B', 'C', 'D', 'E'])->map(fn ($n) => $this->participant($n))->all();

        // A: PBB avg 90, Formasi avg 80 → 90×0.6 + 80×0.4 = 86
        $this->sentSheet($a, $this->judge, [100, 70]);
        $this->sentSheet($a, $second, [80, 90], ScoreSheetStatus::Locked);
        // B and C tie at 80
        $this->sentSheet($b, $this->judge, [80, 80]);
        $this->sentSheet($c, $this->judge, [80, 80]);
        // D: missing Formasi → skipped. E: only a draft → skipped.
        $this->sentSheet($d, $this->judge, [99]);
        $this->sentSheet($e, $this->judge, [100, 100], ScoreSheetStatus::Draft);

        $this->assertSame(3, app(CompetitionJudgingService::class)->calculateResults($this->round));

        $results = $this->round->results()->with('participant')->get()->mapWithKeys(fn ($result) => [$result->participant->name => [(float) $result->weighted_score, $result->rank]]);
        $this->assertEquals(['Tim A' => [86.0, 1], 'Tim B' => [80.0, 2], 'Tim C' => [80.0, 2]], $results->all());

        $this->actingAs($this->admin)->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'hasil']))->assertSee('Tim A');
    }

    public function test_round_can_only_be_judged_when_weights_total_exactly_100(): void
    {
        $participant = $this->participant('A1');
        $round = CompetitionRound::create(['competition_category_id' => $this->category->id, 'name' => 'Semifinal']);
        $round->criteria()->create(['name' => 'PBB', 'weight' => 60, 'minimum_score' => 0, 'maximum_score' => 100]);
        $varisi = $round->criteria()->create(['name' => 'Variasi', 'weight' => 30, 'minimum_score' => 0, 'maximum_score' => 100]);
        $round->judges()->attach($this->judge);

        $this->judgeSession()->get(route('juri.round', $round))->assertSee('Babak ini belum bisa dinilai');
        $this->judgeSession()->get(route('juri.score.show', [$round, $participant]))->assertDontSee('Kirim Skor Final');
        $this->judgeSession()->post(route('juri.score.save', [$round, $participant]), ['scores' => [$varisi->id => ['score' => 80]]])
            ->assertSessionHasErrors('submit');
        $this->assertSame(0, CompetitionJudging::count(), 'a rejected save must not create a sheet');

        $this->actingAs($this->admin)->post(route('dashboard.content.competitions.judging.criteria.store', [$this->listing, $round]), [
            'name' => 'Formasi', 'weight' => 10, 'minimum_score' => 0, 'maximum_score' => 100,
        ])->assertSessionHasNoErrors();

        $this->judgeSession()->post(route('juri.score.save', [$round, $participant]), ['scores' => [$varisi->id => ['score' => 80]]])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, CompetitionJudging::count());
    }

    public function test_criterion_weight_is_required(): void
    {
        $this->actingAs($this->admin)->post(route('dashboard.content.competitions.judging.criteria.store', [$this->listing, $this->round]), [
            'name' => 'Tanpa Bobot', 'minimum_score' => 0, 'maximum_score' => 100,
        ])->assertSessionHasErrors('weight');
    }

    public function test_criteria_are_frozen_once_judging_starts(): void
    {
        $participant = $this->participant('A1');
        [$pbb, $formasi] = $this->round->criteria;
        $this->actingAs($this->admin)->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'penjurian']))
            ->assertSee(route('dashboard.content.competitions.judging.criteria.update', [$this->listing, $formasi]), false);

        $this->judgeSession()->post(route('juri.score.save', [$this->round, $participant]), ['scores' => [$pbb->id => ['score' => 80]]])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->admin);

        $this->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'penjurian']))
            ->assertDontSee(route('dashboard.content.competitions.judging.criteria.update', [$this->listing, $formasi]), false)
            ->assertSee('kriteria terkunci');

        $this->post(route('dashboard.content.competitions.judging.criteria.store', [$this->listing, $this->round]), [
            'name' => 'Baru', 'weight' => 20, 'minimum_score' => 0, 'maximum_score' => 100,
        ])->assertSessionHasErrors('criterion');

        $this->put(route('dashboard.content.competitions.judging.criteria.update', [$this->listing, $formasi]), [
            'name' => 'Formasi & Perpindahan', 'weight' => 40, 'minimum_score' => 0, 'maximum_score' => 100,
        ])->assertSessionHasErrors('criterion');

        $this->delete(route('dashboard.content.competitions.judging.criteria.destroy', [$this->listing, $formasi]))->assertSessionHasErrors('criterion');

        $this->assertSame(2, $this->round->criteria()->count());
        $this->assertSame('Formasi', $formasi->fresh()->name);
    }

    public function test_copy_public_link_appears_only_after_a_judge_sends_scores(): void
    {
        $participant = $this->participant('A1');
        $url = route('site.competitions.results', $this->listing->slug);
        $this->actingAs($this->admin);

        $this->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'lembar']))->assertDontSee($url);

        $this->sentSheet($participant, $this->judge, [80, 70], ScoreSheetStatus::Draft);
        $this->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'lembar']))->assertDontSee($url);

        CompetitionJudging::firstOrFail()->forceFill(['status' => ScoreSheetStatus::Submitted])->save();
        $this->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'lembar']))
            ->assertSee($url)
            ->assertSee('Salin Link');
    }

    public function test_public_results_stay_hidden_until_published_and_keep_judges_anonymous(): void
    {
        $this->listing->update(['status' => 'published']);
        $second = CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Juri Rahasia', 'status' => 'active']);
        $this->round->judges()->attach($second);
        $participant = $this->participant('A1');
        $this->sentSheet($participant, $this->judge, [90, 80]);
        $this->sentSheet($participant, $second, [70, 60]);
        $url = route('site.competitions.results', $this->listing->slug);

        $this->get($url)->assertOk()->assertSee('Final masih berlangsung')->assertDontSee('Tim A1');

        $this->actingAs($this->admin)->post(route('dashboard.content.competitions.judging.results.publish', [$this->listing, $this->round]))->assertSessionHasNoErrors();

        // Juri 1 total: 90×0.6 + 80×0.4 = 86; Juri 2: 70×0.6 + 60×0.4 = 66; final = average per criterion = 76
        $this->get($url)->assertOk()
            ->assertSee('Tim A1')
            ->assertSee('Juri 1')
            ->assertSee('Juri 2')
            ->assertSee('86')
            ->assertSee('66')
            ->assertSee('76')
            ->assertDontSee('Juri Satu')
            ->assertDontSee('Juri Rahasia');

        $this->get(route('site.competitions.show', $this->listing->slug))->assertSee('Lihat hasil penilaian');

        $this->actingAs($this->admin)->delete(route('dashboard.content.competitions.judging.results.unpublish', [$this->listing, $this->round]));
        $this->get($url)->assertSee('Final masih berlangsung')->assertDontSee('Tim A1');
    }

    public function test_public_results_page_404s_for_draft_listings_and_listings_without_rounds(): void
    {
        $this->get(route('site.competitions.results', $this->listing->slug))->assertNotFound();

        $empty = CompetitionListing::create(['title' => 'Tanpa Babak', 'start_date' => '2030-01-01', 'status' => 'published', 'id_user' => $this->admin->id_user]);
        $this->get(route('site.competitions.results', $empty->slug))->assertNotFound();
    }

    public function test_publishing_needs_complete_scores_and_blocks_reopening(): void
    {
        $participant = $this->participant('A1');
        $this->actingAs($this->admin);

        $this->post(route('dashboard.content.competitions.judging.results.publish', [$this->listing, $this->round]))->assertSessionHasErrors('results');
        $this->assertNull($this->round->fresh()->results_published_at);

        $judging = $this->sentSheet($participant, $this->judge, [80, 70]);
        $this->post(route('dashboard.content.competitions.judging.results.publish', [$this->listing, $this->round]))->assertSessionHasNoErrors();
        $this->assertNotNull($this->round->fresh()->results_published_at);

        $this->post(route('dashboard.content.competitions.judging.judgings.reopen', [$this->listing, $judging]), ['reason' => 'Koreksi'])->assertSessionHasErrors('judging');
        $this->assertSame(ScoreSheetStatus::Submitted, $judging->fresh()->status);
    }

    public function test_send_button_saves_and_sends_in_one_step(): void
    {
        $participant = $this->participant('A1');
        [$pbb, $formasi] = $this->round->criteria;
        $route = route('juri.score.save', [$this->round, $participant]);

        // Incomplete: what was typed is kept as a draft, sending is refused.
        $this->judgeSession()->post($route, ['kirim' => 1, 'scores' => [$pbb->id => ['score' => 85]]])->assertSessionHasErrors('submit');
        $judging = CompetitionJudging::firstOrFail();
        $this->assertSame(ScoreSheetStatus::Draft, $judging->status);
        $this->assertSame(1, $judging->scores()->count());

        $this->judgeSession()->post($route, ['kirim' => 1, 'scores' => [$formasi->id => ['score' => 75]]])->assertSessionHasNoErrors();
        $this->assertSame(ScoreSheetStatus::Submitted, $judging->fresh()->status);
        $this->assertSame(2, $judging->scores()->count());
    }

    public function test_access_code_ignores_spaces_dashes_and_case(): void
    {
        $code = $this->judge->access_code;
        $typed = strtolower(substr($code, 0, 4)).' - '.substr($code, 4);

        $this->post(route('juri.access.store'), ['code' => $typed])->assertRedirect(route('juri.dashboard'));
    }

    public function test_results_tab_explains_why_participants_are_not_ranked(): void
    {
        [$a, $b, $c] = collect(['A1', 'A2', 'A3'])->map(fn ($number) => $this->participant($number))->all();
        $this->sentSheet($a, $this->judge, [80, 70]);
        $this->sentSheet($b, $this->judge, [80, 70], ScoreSheetStatus::Draft);
        app(CompetitionJudgingService::class)->calculateResults($this->round);

        $this->actingAs($this->admin)->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'hasil']))
            ->assertSee('Belum diperingkat (2)')
            ->assertSee('Belum ada nilai yang dikirim juri.')
            ->assertSee('Juri Satu (masih draft)')
            ->assertSee('Juri Satu (belum menilai)');
    }

    public function test_sheet_tab_shows_round_readiness_and_draft_listings_cannot_copy_the_link(): void
    {
        $participant = $this->participant('A1');
        $this->sentSheet($participant, $this->judge, [80, 70]);

        $this->actingAs($this->admin)->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'lembar']))
            ->assertSee('✓ Bobot 100%')
            ->assertSee('1 juri ditugaskan')
            ->assertSee('belum bisa disalin');

        $this->listing->update(['status' => 'published']);
        $this->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'lembar']))
            ->assertDontSee('belum bisa disalin')
            ->assertSee('Buka ↗');
    }

    public function test_new_participant_needs_a_competition_number(): void
    {
        $this->actingAs($this->admin)->post(route('dashboard.content.competitions.judging.participants.store', $this->listing), [
            'competition_category_id' => $this->category->id, 'name' => 'Tanpa Nomor',
        ])->assertSessionHasErrors('competition_number');

        $this->assertSame(0, $this->listing->participants()->count());
    }
}
