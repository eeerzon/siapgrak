<?php

namespace Tests\Feature\ScoreSheet;

use App\Enums\CompetitionEntryStatus;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Judge;
use App\Models\JudgeAssignment;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\Score;
use App\Models\ScoreSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected Event $event;

    protected EventCategory $category;

    protected JudgingRound $round;

    protected JudgingCriteria $pbb;

    protected JudgingCriteria $variasi;

    protected JudgingCriteria $formasi;

    protected JudgingCriteria $kreativitas;

    protected Judge $judge;

    protected CompetitionEntry $entry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $this->category = EventCategory::factory()->create(['event_id' => $this->event->id]);
        $this->round = JudgingRound::factory()->create(['event_category_id' => $this->category->id]);

        $this->pbb = JudgingCriteria::factory()->create([
            'judging_round_id' => $this->round->id,
            'name' => 'PBB',
            'minimum_score' => 0,
            'maximum_score' => 100,
        ]);
        $this->variasi = JudgingCriteria::factory()->create([
            'judging_round_id' => $this->round->id,
            'name' => 'Variasi',
            'minimum_score' => 0,
            'maximum_score' => 50,
        ]);
        $this->formasi = JudgingCriteria::factory()->create([
            'judging_round_id' => $this->round->id,
            'parent_id' => $this->variasi->id,
            'name' => 'Formasi',
            'minimum_score' => 0,
            'maximum_score' => 50,
        ]);
        $this->kreativitas = JudgingCriteria::factory()->create([
            'judging_round_id' => $this->round->id,
            'parent_id' => $this->variasi->id,
            'name' => 'Kreativitas',
            'minimum_score' => 0,
            'maximum_score' => 50,
        ]);

        $this->judge = Judge::factory()->create(['organization_id' => $this->organization->id]);

        $this->entry = CompetitionEntry::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
        ]);
    }

    protected function actingAsJudge(): static
    {
        $this->withSession(['judge_id' => $this->judge->id]);

        return $this;
    }

    public function test_whole_round_judge_sees_every_leaf_criteria(): void
    {
        JudgeAssignment::factory()->create(['judging_round_id' => $this->round->id, 'judge_id' => $this->judge->id]);

        $this->actingAsJudge()
            ->get(route('judge.rounds.entries.show', [$this->round, $this->entry]))
            ->assertOk()
            ->assertSee('PBB')
            ->assertSee('Formasi')
            ->assertSee('Kreativitas')
            ->assertDontSee('Variasi'); // parent with children is not itself scoreable
    }

    public function test_assignment_to_a_specific_leaf_only_shows_that_criteria(): void
    {
        JudgeAssignment::factory()->create([
            'judging_round_id' => $this->round->id,
            'judge_id' => $this->judge->id,
            'judging_criteria_id' => $this->pbb->id,
        ]);

        $this->actingAsJudge()
            ->get(route('judge.rounds.entries.show', [$this->round, $this->entry]))
            ->assertOk()
            ->assertSee('PBB')
            ->assertDontSee('Formasi')
            ->assertDontSee('Kreativitas');
    }

    public function test_assignment_to_a_parent_with_children_expands_to_its_children(): void
    {
        JudgeAssignment::factory()->create([
            'judging_round_id' => $this->round->id,
            'judge_id' => $this->judge->id,
            'judging_criteria_id' => $this->variasi->id,
        ]);

        $this->actingAsJudge()
            ->get(route('judge.rounds.entries.show', [$this->round, $this->entry]))
            ->assertOk()
            ->assertSee('Formasi')
            ->assertSee('Kreativitas')
            ->assertDontSee('PBB');
    }

    public function test_withdrawn_and_disqualified_entries_are_excluded_from_the_list(): void
    {
        JudgeAssignment::factory()->create(['judging_round_id' => $this->round->id, 'judge_id' => $this->judge->id]);

        $withdrawn = CompetitionEntry::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
            'status' => CompetitionEntryStatus::Withdrawn,
        ]);
        $disqualified = CompetitionEntry::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
            'status' => CompetitionEntryStatus::Disqualified,
        ]);

        $response = $this->actingAsJudge()->get(route('judge.rounds.entries.index', $this->round));

        $response->assertOk();
        $response->assertViewHas('entries', function ($entries) use ($withdrawn, $disqualified) {
            return ! $entries->contains('id', $withdrawn->id) && ! $entries->contains('id', $disqualified->id);
        });
    }

    public function test_saving_a_valid_draft_persists_scores_and_lazily_creates_the_sheet(): void
    {
        JudgeAssignment::factory()->create(['judging_round_id' => $this->round->id, 'judge_id' => $this->judge->id]);

        $this->assertDatabaseMissing('score_sheets', [
            'judging_round_id' => $this->round->id,
            'competition_entry_id' => $this->entry->id,
            'judge_id' => $this->judge->id,
        ]);

        $this->actingAsJudge()
            ->post(route('judge.rounds.entries.scores.store', [$this->round, $this->entry]), [
                'scores' => [
                    $this->pbb->id => ['judging_criteria_id' => $this->pbb->id, 'score' => 88.5],
                    $this->formasi->id => ['judging_criteria_id' => $this->formasi->id, 'score' => 0],
                ],
            ])
            ->assertRedirect(route('judge.rounds.entries.show', [$this->round, $this->entry]));

        $sheet = ScoreSheet::where([
            'judging_round_id' => $this->round->id,
            'competition_entry_id' => $this->entry->id,
            'judge_id' => $this->judge->id,
        ])->first();

        $this->assertNotNull($sheet);
        $this->assertSame('88.500', Score::where('score_sheet_id', $sheet->id)->where('judging_criteria_id', $this->pbb->id)->first()->score);
        $this->assertSame('0.000', Score::where('score_sheet_id', $sheet->id)->where('judging_criteria_id', $this->formasi->id)->first()->score);
    }

    public function test_score_above_maximum_is_rejected(): void
    {
        JudgeAssignment::factory()->create(['judging_round_id' => $this->round->id, 'judge_id' => $this->judge->id]);

        $this->actingAsJudge()
            ->post(route('judge.rounds.entries.scores.store', [$this->round, $this->entry]), [
                'scores' => [
                    $this->pbb->id => ['judging_criteria_id' => $this->pbb->id, 'score' => 150],
                ],
            ])
            ->assertSessionHasErrors('scores.'.$this->pbb->id.'.score');

        $this->assertDatabaseMissing('scores', ['judging_criteria_id' => $this->pbb->id]);
    }

    public function test_score_below_minimum_is_rejected(): void
    {
        JudgeAssignment::factory()->create(['judging_round_id' => $this->round->id, 'judge_id' => $this->judge->id]);

        $this->actingAsJudge()
            ->post(route('judge.rounds.entries.scores.store', [$this->round, $this->entry]), [
                'scores' => [
                    $this->pbb->id => ['judging_criteria_id' => $this->pbb->id, 'score' => -1],
                ],
            ])
            ->assertSessionHasErrors('scores.'.$this->pbb->id.'.score');
    }

    public function test_criteria_outside_the_judges_assignment_is_rejected(): void
    {
        // Judge is only assigned to PBB, not Formasi/Kreativitas.
        JudgeAssignment::factory()->create([
            'judging_round_id' => $this->round->id,
            'judge_id' => $this->judge->id,
            'judging_criteria_id' => $this->pbb->id,
        ]);

        $this->actingAsJudge()
            ->post(route('judge.rounds.entries.scores.store', [$this->round, $this->entry]), [
                'scores' => [
                    $this->formasi->id => ['judging_criteria_id' => $this->formasi->id, 'score' => 30],
                ],
            ])
            ->assertSessionHasErrors('scores.'.$this->formasi->id.'.judging_criteria_id');

        $this->assertDatabaseMissing('scores', ['judging_criteria_id' => $this->formasi->id]);
    }

    public function test_entry_from_a_different_category_is_rejected(): void
    {
        JudgeAssignment::factory()->create(['judging_round_id' => $this->round->id, 'judge_id' => $this->judge->id]);

        $foreignEntry = CompetitionEntry::factory()->create();

        $this->actingAsJudge()
            ->post(route('judge.rounds.entries.scores.store', [$this->round, $foreignEntry]), [
                'scores' => [
                    $this->pbb->id => ['judging_criteria_id' => $this->pbb->id, 'score' => 50],
                ],
            ])
            ->assertSessionHasErrors('scores');
    }

    public function test_a_judge_without_any_assignment_cannot_view_the_round(): void
    {
        $this->actingAsJudge()
            ->get(route('judge.rounds.entries.index', $this->round))
            ->assertForbidden();
    }

    public function test_empty_score_does_not_create_a_row_and_clears_an_existing_one(): void
    {
        JudgeAssignment::factory()->create(['judging_round_id' => $this->round->id, 'judge_id' => $this->judge->id]);

        $this->actingAsJudge()->post(route('judge.rounds.entries.scores.store', [$this->round, $this->entry]), [
            'scores' => [
                $this->pbb->id => ['judging_criteria_id' => $this->pbb->id, 'score' => 70],
            ],
        ]);
        $this->assertDatabaseHas('scores', ['judging_criteria_id' => $this->pbb->id, 'score' => 70.000]);

        $this->actingAsJudge()->post(route('judge.rounds.entries.scores.store', [$this->round, $this->entry]), [
            'scores' => [
                $this->pbb->id => ['judging_criteria_id' => $this->pbb->id, 'score' => null],
            ],
        ]);

        $this->assertDatabaseMissing('scores', ['judging_criteria_id' => $this->pbb->id]);
    }
}
