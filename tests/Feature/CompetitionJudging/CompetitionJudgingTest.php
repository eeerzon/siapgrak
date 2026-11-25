<?php

namespace Tests\Feature\CompetitionJudging;

use App\Enums\ScoreSheetStatus;
use App\Http\Middleware\EnsureCompetitionJudgeSession;
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
        $judge ??= $this->judge;

        return $this->withSession([
            EnsureCompetitionJudgeSession::SESSION_KEY => $judge->id,
            EnsureCompetitionJudgeSession::CODE_KEY => EnsureCompetitionJudgeSession::fingerprint($judge),
        ]);
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
        [$a, $b, $c, $d, $e, $f] = collect(['A', 'B', 'C', 'D', 'E', 'F'])->map(fn ($n) => $this->participant($n))->all();

        // A: PBB avg 90, Formasi avg 80 → 90×0.6 + 80×0.4 = 86
        $this->sentSheet($a, $this->judge, [100, 70]);
        $this->sentSheet($a, $second, [80, 90], ScoreSheetStatus::Locked);
        // B and C tie at 80
        foreach ([$b, $c] as $tied) {
            $this->sentSheet($tied, $this->judge, [80, 80]);
            $this->sentSheet($tied, $second, [80, 80]);
        }
        // D: missing Formasi → skipped. E: second judge still in draft → skipped.
        $this->sentSheet($d, $this->judge, [99]);
        $this->sentSheet($d, $second, [99]);
        $this->sentSheet($e, $this->judge, [100, 100]);
        $this->sentSheet($e, $second, [100, 100], ScoreSheetStatus::Draft);
        // F: complete sheet from one judge, the other has not judged yet → skipped, never ranked on one judge alone.
        $this->sentSheet($f, $this->judge, [100, 100]);

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
        // Second round of the category: the team has to be moved on to it.
        $round->participants()->attach($participant);

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

    public function test_participant_waits_for_every_assigned_judge_before_ranking(): void
    {
        $second = CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Juri Dua', 'status' => 'active']);
        $this->round->judges()->attach($second);
        $participant = $this->participant('01');
        $this->sentSheet($participant, $this->judge, [90, 90]);

        $this->assertSame(0, app(CompetitionJudgingService::class)->calculateResults($this->round));
        $this->actingAs($this->admin)->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'hasil']))
            ->assertSee('Baru 1 dari 2 juri yang mengirim nilai.')
            ->assertSee('Juri Dua (belum menilai)');

        $this->sentSheet($participant, $second, [70, 70]);
        $this->assertSame(1, app(CompetitionJudgingService::class)->calculateResults($this->round));
        $this->assertEquals(80.0, (float) $this->round->results()->first()->weighted_score);
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

    public function test_new_access_code_ends_sessions_made_with_the_old_one(): void
    {
        $this->judgeSession()->get(route('juri.dashboard'))->assertOk();

        $this->actingAs($this->admin)->patch(route('dashboard.content.judges.access-code', $this->judge))->assertSessionHasNoErrors();

        $this->get(route('juri.dashboard'))->assertRedirect(route('juri.access.create'));
    }

    public function test_judges_cannot_score_after_the_round_is_published(): void
    {
        $sent = $this->participant('01');
        $late = $this->participant('02');
        $this->sentSheet($sent, $this->judge, [80, 70]);
        $this->round->forceFill(['results_published_at' => now()])->save();
        $criteria = $this->round->criteria;

        $this->judgeSession()->post(route('juri.score.save', [$this->round, $late]), [
            'scores' => [$criteria[0]->id => ['score' => 90], $criteria[1]->id => ['score' => 90]],
            'kirim' => 1,
        ])->assertSessionHasErrors('submit');

        $this->assertFalse($this->round->judgings()->where('competition_participant_id', $late->id)->exists());
    }

    public function test_confirm_dialogs_escape_names_with_quotes(): void
    {
        $this->listing->participants()->create(['competition_category_id' => $this->category->id, 'name' => "Ma'arif');alert(1);//", 'competition_number' => '07']);

        $this->actingAs($this->admin)->get(route('dashboard.content.competitions.judging.show', $this->listing))
            ->assertOk()
            ->assertSee('data-confirm-title="Hapus Ma&#039;arif&#039;);alert(1);//?"', false)
            ->assertDontSee('onsubmit="return confirm(', false);
    }

    public function test_publish_confirm_warns_about_unsent_sheets_and_the_toast_reports_the_count(): void
    {
        $second = CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Juri Dua', 'status' => 'active']);
        $this->round->judges()->attach($second);
        $complete = $this->participant('01');
        $pending = $this->participant('02');
        $this->sentSheet($complete, $this->judge, [80, 70]);
        $this->sentSheet($complete, $second, [90, 60]);
        $this->sentSheet($pending, $this->judge, [70, 70], ScoreSheetStatus::Draft);

        $this->actingAs($this->admin)->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'hasil']))
            ->assertOk()
            ->assertSee('2 lembar nilai belum dikirim juri (1 masih draft)', false)
            ->assertSee('1 peserta belum dinilai semua juri', false)
            ->assertSee('data-confirm-button="Tetap publikasikan"', false)
            ->assertSee('data-confirm-icon="warning"', false);

        $this->post(route('dashboard.content.competitions.judging.results.publish', [$this->listing, $this->round]))
            ->assertSessionHas('success', 'Peringkat SMA · Final dihitung ulang untuk 1 peserta dan sudah tampil di halaman publik.');
    }

    public function test_each_category_has_its_own_public_results_link(): void
    {
        $this->listing->update(['status' => 'published']);
        $smp = $this->listing->categories()->create(['name' => 'SMP']);
        CompetitionRound::create(['competition_category_id' => $smp->id, 'name' => 'Penyisihan SMP']);
        $other = CompetitionListing::create(['title' => 'Lomba Lain', 'start_date' => '2030-01-01', 'status' => 'published', 'id_user' => $this->admin->id_user]);
        $foreign = $other->categories()->create(['name' => 'Asing']);

        $this->get(route('site.competitions.results', $this->listing->slug))
            ->assertOk()->assertSee('Final')->assertSee('Penyisihan SMP')->assertSee('Semua kategori');

        $this->get(route('site.competitions.results.category', [$this->listing->slug, $smp->id]))
            ->assertOk()->assertSee('Hasil Penilaian SMP')->assertSee('Penyisihan SMP')->assertDontSee('<h2 class="mt-4 text-2xl font-bold tracking-[-0.02em] md:text-[32px]">Final</h2>', false);

        // A category of another listing, or one without rounds, is not a valid link.
        $this->get(route('site.competitions.results.category', [$this->listing->slug, $foreign->id]))->assertNotFound();
        $empty = $this->listing->categories()->create(['name' => 'Tanpa Babak']);
        $this->get(route('site.competitions.results.category', [$this->listing->slug, $empty->id]))->assertNotFound();

        $participant = $this->participant('01');
        $this->sentSheet($participant, $this->judge, [80, 70]);
        $this->actingAs($this->admin)->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'hasil']))
            ->assertSee(route('site.competitions.results', $this->listing->slug), false)
            ->assertSee(route('site.competitions.results.category', [$this->listing->slug, $smp->id]), false)
            ->assertSee(route('site.competitions.results.category', [$this->listing->slug, $this->category->id]), false);
    }

    public function test_admin_tabs_show_one_category_at_a_time_and_keep_it_across_tabs(): void
    {
        $smp = $this->listing->categories()->create(['name' => 'SMP', 'display_order' => 2]);
        $this->participant('SMA-01');
        $this->listing->participants()->create(['competition_category_id' => $smp->id, 'name' => 'Tim SMP Satu', 'competition_number' => 'SMP-01']);
        $this->actingAs($this->admin);

        // Default: first category.
        $this->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'peserta']))
            ->assertSee('Tim SMA-01')->assertDontSee('Tim SMP Satu');

        $this->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'peserta', 'kategori' => $smp->id]))
            ->assertSee('Tim SMP Satu')->assertDontSee('Tim SMA-01')
            ->assertSee(e(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'hasil', 'kategori' => $smp->id])), false)
            ->assertSee('<input type="hidden" name="competition_category_id" value="'.$smp->id.'">', false);

        $this->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'penjurian', 'kategori' => $smp->id]))
            ->assertSee('Belum ada babak di SMP.')->assertDontSee('<h2 class="font-semibold text-navy-900">Final</h2>', false);

        $this->post(route('dashboard.content.competitions.judging.rounds.store', $this->listing), ['competition_category_id' => $smp->id, 'name' => 'Penyisihan'])
            ->assertRedirect(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'penjurian', 'kategori' => $smp->id]));
    }

    public function test_public_results_show_one_round_per_category_defaulting_to_the_latest_published(): void
    {
        $this->listing->update(['status' => 'published']);
        $this->round->update(['name' => 'Penyisihan', 'display_order' => 1]);
        $final = CompetitionRound::create(['competition_category_id' => $this->category->id, 'name' => 'Final Besar', 'display_order' => 2]);
        $participant = $this->participant('01');
        $this->sentSheet($participant, $this->judge, [80, 70]);
        app(CompetitionJudgingService::class)->publishResults($this->round);
        $url = fn (?CompetitionRound $round = null) => $round
            ? route('site.competitions.results.round', [$this->listing->slug, $this->category->id, $round->id])
            : route('site.competitions.results.category', [$this->listing->slug, $this->category->id]);

        // Final is not published yet, so the latest published round (Penyisihan) opens by default.
        $this->get($url())->assertOk()->assertSee('Tim 01')->assertDontSee('Final Besar masih berlangsung')
            ->assertSee($url($final), false)->assertSee('· berlangsung', false);
        $this->get($url($final))->assertOk()->assertSee('Final Besar masih berlangsung')->assertDontSee('Tim 01');

        // A round of another category is not a valid link.
        $smp = $this->listing->categories()->create(['name' => 'SMP']);
        $smpRound = CompetitionRound::create(['competition_category_id' => $smp->id, 'name' => 'Final SMP']);
        $this->get(route('site.competitions.results.round', [$this->listing->slug, $this->category->id, $smpRound->id]))->assertNotFound();
    }

    public function test_later_rounds_only_have_the_teams_moved_on_to_them(): void
    {
        [$a, $b, $c] = collect(['A', 'B', 'C'])->map(fn ($n) => $this->participant($n))->all();
        $final = CompetitionRound::create(['competition_category_id' => $this->category->id, 'name' => 'Final Besar', 'display_order' => 2]);
        $final->criteria()->create(['name' => 'PBB', 'weight' => 100, 'minimum_score' => 0, 'maximum_score' => 100]);
        $final->judges()->attach($this->judge);
        $service = app(CompetitionJudgingService::class);

        // First round: the whole category. Later round: nobody until the admin moves teams on.
        $this->assertCount(3, $service->entrants($this->round->fresh()));
        $this->assertCount(0, $service->entrants($final->fresh()));
        $this->judgeSession()->get(route('juri.score.show', [$final, $a]))->assertNotFound();

        // "Ambil 2 teratas" needs a ranking first.
        $this->actingAs($this->admin)->post(route('dashboard.content.competitions.judging.round-participants.top', [$this->listing, $final]), ['top' => 2])
            ->assertSessionHasErrors('round_participants');

        $this->sentSheet($a, $this->judge, [90, 90]);
        $this->sentSheet($b, $this->judge, [80, 80]);
        $this->sentSheet($c, $this->judge, [80, 80]);
        $service->publishResults($this->round);

        // B and C tie at rank 2, so asking for the top 2 moves on all three.
        $this->post(route('dashboard.content.competitions.judging.round-participants.top', [$this->listing, $final]), ['top' => 2])
            ->assertRedirect(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'penjurian', 'kategori' => $this->category->id]));
        $this->assertEqualsCanonicalizing([$a->id, $b->id, $c->id], $final->participants()->pluck('competition_participants.id')->all());

        // Taking a team out works until it has a sheet in that round.
        $this->delete(route('dashboard.content.competitions.judging.round-participants.destroy', [$this->listing, $final, $b]))->assertSessionHasNoErrors();
        $this->judgeSession()->post(route('juri.score.save', [$final, $a]), ['scores' => [$final->criteria()->first()->id => ['score' => 88]], 'kirim' => 1])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->delete(route('dashboard.content.competitions.judging.round-participants.destroy', [$this->listing, $final, $a]))
            ->assertSessionHasErrors('round_participants');

        // Manual add only accepts teams of the same category; the first round has no team list to edit.
        $smp = $this->listing->categories()->create(['name' => 'SMP']);
        $outsider = $this->listing->participants()->create(['competition_category_id' => $smp->id, 'name' => 'Tim Luar', 'competition_number' => 'X1']);
        $this->post(route('dashboard.content.competitions.judging.round-participants.store', [$this->listing, $final]), ['participant_ids' => [$outsider->id]])
            ->assertSessionHasErrors('participant_ids.0');
        $this->post(route('dashboard.content.competitions.judging.round-participants.store', [$this->listing, $final]), ['participant_ids' => [$c->id]])
            ->assertSessionHasNoErrors();
        $this->post(route('dashboard.content.competitions.judging.round-participants.store', [$this->listing, $this->round]), ['participant_ids' => [$a->id]])
            ->assertNotFound();

        // Ranking and the judge panel only count the round's teams.
        $this->assertSame(1, $service->calculateResults($final->fresh()));
        $this->judgeSession()->get(route('juri.round', $final))->assertSee('Tim A')->assertSee('Tim C')->assertDontSee('Tim B');
        $this->actingAs($this->admin)->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'penjurian']))
            ->assertSee('Peserta babak ini (2)')->assertSee('Ambil peserta teratas dari Final');
    }

    public function test_finished_rounds_start_collapsed_in_the_sheets_and_results_tabs(): void
    {
        $participant = $this->participant('01');
        $this->sentSheet($participant, $this->judge, [80, 70]);
        $this->actingAs($this->admin);
        $page = fn (string $tab) => $this->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => $tab]));

        // Still running: shown in full, no toggle.
        $page('lembar')->assertSee('x-data="{ open: true }"', false)->assertDontSee('Tampilkan detail');
        $page('hasil')->assertSee('x-data="{ open: true }"', false)->assertDontSee('Tampilkan detail');

        app(CompetitionJudgingService::class)->publishResults($this->round);

        $page('lembar')->assertSee('x-data="{ open: false }"', false)->assertSee('Tampilkan detail')
            ->assertSee('1/1</span> lembar terkirim', false);
        $page('hasil')->assertSee('x-data="{ open: false }"', false)->assertSee('Tampilkan detail')
            ->assertSee('1 peserta diperingkat');
        // The rounds tab is never collapsed.
        $page('penjurian')->assertDontSee('Tampilkan detail')->assertSee('Selesai · hasil diumumkan');
    }

    public function test_round_team_list_rejects_foreign_teams_and_follows_category_moves(): void
    {
        $final = CompetitionRound::create(['competition_category_id' => $this->category->id, 'name' => 'Final Besar', 'display_order' => 2]);
        $team = $this->participant('01');
        $final->participants()->attach($team);
        $this->actingAs($this->admin);

        // A team of another admin's listing: 404, and its name is never echoed back.
        $other = User::factory()->create(['is_platform_admin' => true]);
        $foreignListing = CompetitionListing::create(['title' => 'Lain', 'start_date' => '2030-01-01', 'status' => 'draft', 'id_user' => $other->id_user]);
        $foreignCategory = $foreignListing->categories()->create(['name' => 'X']);
        $secret = $foreignListing->participants()->create(['competition_category_id' => $foreignCategory->id, 'name' => 'Nama Rahasia', 'competition_number' => 'Z9']);
        $this->delete(route('dashboard.content.competitions.judging.round-participants.destroy', [$this->listing, $final, $secret]))->assertNotFound();
        $this->assertNull(session('success'));

        // Moving a not-yet-judged team to another category takes it out of the old category's later rounds.
        $smp = $this->listing->categories()->create(['name' => 'SMP']);
        $this->put(route('dashboard.content.competitions.judging.participants.update', [$this->listing, $team]), [
            'competition_category_id' => $smp->id, 'name' => 'Tim 01', 'competition_number' => '01',
        ])->assertSessionHasNoErrors();
        $this->assertFalse($final->participants()->whereKey($team->id)->exists());
        $this->assertCount(0, app(CompetitionJudgingService::class)->entrants($final->fresh()));
    }

    public function test_saving_scores_returns_the_judge_to_the_team_list(): void
    {
        $participant = $this->participant('01');
        [$pbb, $formasi] = $this->round->criteria->all();
        $save = fn (array $data) => $this->judgeSession()->from(route('juri.score.show', [$this->round, $participant]))
            ->post(route('juri.score.save', [$this->round, $participant]), $data);

        // Invalid input stays on the score form.
        $save(['scores' => [$pbb->id => ['score' => 500]]])->assertRedirect(route('juri.score.show', [$this->round, $participant]));

        $save(['scores' => [$pbb->id => ['score' => 80]]])
            ->assertRedirect(route('juri.round', $this->round))->assertSessionHas('success', 'Draft nilai Tim 01 tersimpan.');
        $save(['scores' => [$pbb->id => ['score' => 80], $formasi->id => ['score' => 75]], 'kirim' => 1])
            ->assertRedirect(route('juri.round', $this->round))->assertSessionHas('success', 'Nilai Tim 01 terkirim ke panitia.');
    }

    public function test_manual_pick_list_follows_the_previous_round_ranking(): void
    {
        [$a, $b, $c] = collect(['A', 'B', 'C'])->map(fn ($n) => $this->participant($n))->all();
        $this->sentSheet($a, $this->judge, [70, 70]);
        $this->sentSheet($b, $this->judge, [90, 90]);
        $this->sentSheet($c, $this->judge, [80, 80]);
        app(CompetitionJudgingService::class)->publishResults($this->round);
        CompetitionRound::create(['competition_category_id' => $this->category->id, 'name' => 'Babak II', 'display_order' => 2]);

        // Best first by the previous round's ranking, with the rank and score shown.
        $this->actingAs($this->admin)->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'penjurian']))
            ->assertSee('Pilih manual (3 peserta Final lainnya, urut peringkat)')
            ->assertSeeInOrder(['Tim B', '>90</span>', 'Tim C', '>80</span>', 'Tim A', '>70</span>'], false)
            ->assertSee('title="Peringkat 1 di Final"', false);

        // Chips of teams already in the round follow the same order, with the rank but no score.
        CompetitionRound::where('name', 'Babak II')->first()->participants()->attach([$a->id, $b->id]);
        $this->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'penjurian']))
            ->assertSeeInOrder(['Peserta babak ini (2)', 'title="Peringkat 1 di Final">1</span>', 'Tim B', 'title="Peringkat 3 di Final">3</span>', 'Tim A', 'Pilih manual'], false);
    }

    public function test_a_round_is_complete_when_every_judge_sent_every_team_and_gates_the_next_round(): void
    {
        $second = CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Juri Dua', 'status' => 'active']);
        $spare = CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Juri Cadangan', 'status' => 'active']);
        $this->round->judges()->attach($second);
        [$a, $b] = collect(['A', 'B'])->map(fn ($n) => $this->participant($n))->all();
        $next = CompetitionRound::create(['competition_category_id' => $this->category->id, 'name' => 'Babak II', 'display_order' => 2]);
        $service = app(CompetitionJudgingService::class);
        $this->actingAs($this->admin);
        $page = fn () => $this->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'penjurian']));

        // 3 of 4 sheets sent, the 4th still a draft: not complete.
        $this->sentSheet($a, $this->judge, [80, 80]);
        $this->sentSheet($a, $second, [80, 80]);
        $this->sentSheet($b, $this->judge, [70, 70]);
        $draft = $this->sentSheet($b, $second, [70, 70], ScoreSheetStatus::Draft);
        $this->assertFalse($service->isComplete($this->round->fresh()));
        $page()->assertSee('Final masih berlangsung')->assertSee('Sekarang 3 dari 4 lembar terkirim.')
            ->assertDontSee('Pilih manual')->assertSee('Tugaskan juri');
        $this->post(route('dashboard.content.competitions.judging.round-participants.store', [$this->listing, $next]), ['participant_ids' => [$a->id]])
            ->assertSessionHasErrors('round_participants');
        $service->calculateResults($this->round);
        $this->post(route('dashboard.content.competitions.judging.round-participants.top', [$this->listing, $next]), ['top' => 1])
            ->assertSessionHasErrors('round_participants');

        // Last sheet sent: complete. No new judges; the next round opens its team pick.
        $draft->forceFill(['status' => ScoreSheetStatus::Submitted])->save();
        $this->assertTrue($service->isComplete($this->round->fresh()));
        $page()->assertSee('Selesai dinilai · 4/4 lembar terkirim')
            ->assertSee('Semua juri sudah selesai menilai babak ini')
            ->assertSee('Hasil Final belum dipublikasikan')->assertDontSee('Pilih manual');
        $this->post(route('dashboard.content.competitions.judging.round-participants.store', [$this->listing, $next]), ['participant_ids' => [$a->id]])
            ->assertSessionHasErrors('round_participants');

        // Finished and published: the next round can pick its teams.
        $service->publishResults($this->round->fresh());
        $page()->assertDontSee('lembar terkirim.</p>', false)->assertDontSee('belum dipublikasikan</p>', false)->assertSee('Pilih manual');
        $this->post(route('dashboard.content.competitions.judging.judges.assign', [$this->listing, $this->round]), ['competition_judge_id' => $spare->id])
            ->assertSessionHasErrors('judge');
        $this->post(route('dashboard.content.competitions.judging.round-participants.store', [$this->listing, $next]), ['participant_ids' => [$a->id]])
            ->assertSessionHasNoErrors();
    }

    public function test_adding_teams_to_a_later_round_follows_the_closing_rules_and_resets_the_next_round(): void
    {
        $teams = collect(['A', 'B', 'C', 'D'])->map(fn ($n) => $this->participant($n));
        foreach ($teams as $i => $team) {
            $this->sentSheet($team, $this->judge, [90 - $i * 5, 90 - $i * 5]);
        }
        $service = app(CompetitionJudgingService::class);
        $service->publishResults($this->round);
        $two = CompetitionRound::create(['competition_category_id' => $this->category->id, 'name' => 'Babak II', 'display_order' => 2]);
        $two->criteria()->create(['name' => 'PBB', 'weight' => 100, 'minimum_score' => 0, 'maximum_score' => 100]);
        $two->judges()->attach($this->judge);
        $three = CompetitionRound::create(['competition_category_id' => $this->category->id, 'name' => 'Babak III', 'display_order' => 3]);
        $three->judges()->attach($this->judge);
        [$a, $b, $c, $d] = $teams->all();
        $this->actingAs($this->admin);
        $add = fn (CompetitionRound $round, array $ids) => $this->post(route('dashboard.content.competitions.judging.round-participants.store', [$this->listing, $round]), ['participant_ids' => $ids]);
        $penjurian = fn () => $this->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'penjurian']));

        // Babak II: top 2 of Final, all judged; Babak III picks from Babak II's teams only.
        $add($two, [$a->id, $b->id])->assertSessionHasNoErrors();
        foreach ([$a, $b] as $i => $team) {
            $this->judgeSession()->post(route('juri.score.save', [$two, $team]), ['scores' => [$two->criteria()->first()->id => ['score' => 80 - $i]], 'kirim' => 1]);
        }
        $service->publishResults($two->fresh());
        $penjurian()->assertSee('Pilih manual (2 peserta Babak II lainnya, urut peringkat)');
        $add($three, [$c->id])->assertSessionHasErrors('participant_ids.0');
        $add($three, [$a->id])->assertSessionHasNoErrors();

        // Babak III has a team but no sheet yet: taking Babak II off the public page clears Babak III's teams
        // (they came from that ranking), then adding to Babak II is allowed.
        $this->delete(route('dashboard.content.competitions.judging.results.unpublish', [$this->listing, $two]))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Peserta Babak III dikosongkan'));
        $this->assertSame(0, $three->participants()->count());
        $add($two, [$c->id])->assertSessionHasNoErrors();
        $penjurian()->assertSee('Babak II masih berlangsung');

        // Babak II published: closed until it is taken off the public page.
        $this->judgeSession()->post(route('juri.score.save', [$two, $c]), ['scores' => [$two->criteria()->first()->id => ['score' => 70]], 'kirim' => 1]);
        $service->publishResults($two->fresh());
        $add($two, [$d->id])->assertSessionHasErrors('round_participants');
        $penjurian()->assertSee('Tarik dulu dari publik di tab Hasil');

        // Babak III started: Babak II's team list is frozen for good.
        $service->unpublishResults($two->fresh());
        $three->participants()->attach($a);
        $three->criteria()->create(['name' => 'PBB', 'weight' => 100, 'minimum_score' => 0, 'maximum_score' => 100]);
        $this->judgeSession()->post(route('juri.score.save', [$three, $a]), ['scores' => [$three->criteria()->first()->id => ['score' => 88]]]);
        $this->actingAs($this->admin);
        $add($two, [$d->id])->assertSessionHasErrors('round_participants');
        $penjurian()->assertSee('Babak III sudah mulai dinilai, jadi peserta Babak II tidak bisa ditambah lagi.');

        // Babak II published again: it cannot be taken off the public page any more (Babak III is judged from it),
        // and no new judge can be assigned to a public round.
        $this->judgeSession()->post(route('juri.score.save', [$two, $d]), []);
        $service->publishResults($two->fresh());
        $this->actingAs($this->admin);
        $this->delete(route('dashboard.content.competitions.judging.results.unpublish', [$this->listing, $two]))->assertSessionHasErrors('results');
        $this->assertTrue($two->fresh()->isPublished());
        $this->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'hasil']))
            ->assertSee('title="Babak III sudah mulai dinilai memakai peringkat ini, jadi hasilnya tidak bisa ditarik lagi."', false);
        $spare = CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Juri Cadangan', 'status' => 'active']);
        $this->post(route('dashboard.content.competitions.judging.judges.assign', [$this->listing, $two]), ['competition_judge_id' => $spare->id])
            ->assertSessionHasErrors('judge');
    }

    public function test_publishing_too_early_tells_the_admin_how_to_let_judges_finish(): void
    {
        $second = CompetitionJudge::create(['id_user' => $this->admin->id_user, 'name' => 'Juri Dua', 'status' => 'active']);
        $this->round->judges()->attach($second);
        $a = $this->participant('A');
        $b = $this->participant('B');
        $this->sentSheet($a, $this->judge, [80, 80]);
        $this->sentSheet($a, $second, [80, 80]);
        $this->sentSheet($b, $this->judge, [70, 70]);
        app(CompetitionJudgingService::class)->publishResults($this->round);
        $next = CompetitionRound::create(['competition_category_id' => $this->category->id, 'name' => 'Babak II', 'display_order' => 2]);

        $this->actingAs($this->admin)->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'penjurian']))
            ->assertSee('Hasil Final dipublikasikan sebelum semua juri selesai (3 dari 4 lembar terkirim)');
        $this->post(route('dashboard.content.competitions.judging.round-participants.store', [$this->listing, $next]), ['participant_ids' => [$a->id]])
            ->assertSessionHasErrors(['round_participants' => 'Hasil Final dipublikasikan sebelum semua juri selesai menilai. Tarik dulu hasilnya di tab Hasil supaya juri bisa menyelesaikan penilaian, lalu publikasikan lagi.']);
    }

    public function test_lock_feature_is_hidden_and_locked_sheets_read_as_sent(): void
    {
        $sent = $this->participant('01');
        $locked = $this->participant('02');
        $this->sentSheet($sent, $this->judge, [80, 70]);
        $this->sentSheet($locked, $this->judge, [80, 70], ScoreSheetStatus::Locked);

        $this->actingAs($this->admin)->get(route('dashboard.content.competitions.judging.show', [$this->listing, 'tab' => 'lembar']))
            ->assertDontSee('>Kunci</button>', false)->assertDontSee('Dikunci')->assertSee('Terkirim');
        $this->judgeSession()->get(route('juri.round', $this->round))->assertDontSee('Dikunci');
        $this->judgeSession()->get(route('juri.score.show', [$this->round, $locked]))
            ->assertSee('Nilai sudah dikirim.')->assertDontSee('dikunci panitia');
    }
}
