<?php

namespace Tests\Feature\ScoreSheet;

use App\Enums\ScoreSheetStatus;
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

class SubmitTest extends TestCase
{
    use RefreshDatabase;

    protected JudgingRound $round;

    protected JudgingCriteria $pbb;

    protected JudgingCriteria $variasi;

    protected Judge $judge;

    protected CompetitionEntry $entry;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $organization->id]);
        $category = EventCategory::factory()->create(['event_id' => $event->id]);
        $this->round = JudgingRound::factory()->create(['event_category_id' => $category->id]);

        $this->pbb = JudgingCriteria::factory()->create([
            'judging_round_id' => $this->round->id,
            'minimum_score' => 0,
            'maximum_score' => 100,
        ]);
        $this->variasi = JudgingCriteria::factory()->create([
            'judging_round_id' => $this->round->id,
            'minimum_score' => 0,
            'maximum_score' => 50,
        ]);

        $this->judge = Judge::factory()->create(['organization_id' => $organization->id]);
        JudgeAssignment::factory()->create(['judging_round_id' => $this->round->id, 'judge_id' => $this->judge->id]);

        $this->entry = CompetitionEntry::factory()->create([
            'event_id' => $event->id,
            'event_category_id' => $category->id,
        ]);
    }

    protected function actingAsJudge(): static
    {
        $this->withSession(['judge_id' => $this->judge->id]);

        return $this;
    }

    protected function fillAllScores(): ScoreSheet
    {
        $sheet = ScoreSheet::factory()->create([
            'judging_round_id' => $this->round->id,
            'competition_entry_id' => $this->entry->id,
            'judge_id' => $this->judge->id,
        ]);
        Score::factory()->create(['score_sheet_id' => $sheet->id, 'judging_criteria_id' => $this->pbb->id, 'score' => 80]);
        Score::factory()->create(['score_sheet_id' => $sheet->id, 'judging_criteria_id' => $this->variasi->id, 'score' => 40]);

        return $sheet;
    }

    public function test_submit_succeeds_when_every_criteria_is_filled(): void
    {
        $this->fillAllScores();

        $this->actingAsJudge()
            ->post(route('judge.rounds.entries.submit', [$this->round, $this->entry]))
            ->assertRedirect(route('judge.rounds.entries.show', [$this->round, $this->entry]));

        $sheet = ScoreSheet::where([
            'judging_round_id' => $this->round->id,
            'competition_entry_id' => $this->entry->id,
            'judge_id' => $this->judge->id,
        ])->first();

        $this->assertSame('submitted', $sheet->status->value);
        $this->assertNotNull($sheet->submitted_at);
    }

    public function test_submit_fails_when_a_criteria_is_missing(): void
    {
        $sheet = ScoreSheet::factory()->create([
            'judging_round_id' => $this->round->id,
            'competition_entry_id' => $this->entry->id,
            'judge_id' => $this->judge->id,
        ]);
        Score::factory()->create(['score_sheet_id' => $sheet->id, 'judging_criteria_id' => $this->pbb->id, 'score' => 80]);
        // Variasi left empty on purpose.

        $this->actingAsJudge()
            ->post(route('judge.rounds.entries.submit', [$this->round, $this->entry]))
            ->assertSessionHasErrors('submit');

        $this->assertSame('draft', $sheet->fresh()->status->value);
    }

    public function test_submitting_an_already_submitted_sheet_is_rejected(): void
    {
        $sheet = $this->fillAllScores();
        $sheet->status = ScoreSheetStatus::Submitted;
        $sheet->submitted_at = now();
        $sheet->save();

        $this->actingAsJudge()
            ->post(route('judge.rounds.entries.submit', [$this->round, $this->entry]))
            ->assertForbidden();
    }

    public function test_judge_cannot_save_draft_once_submitted(): void
    {
        $sheet = $this->fillAllScores();
        $sheet->status = ScoreSheetStatus::Submitted;
        $sheet->submitted_at = now();
        $sheet->save();

        $this->actingAsJudge()
            ->post(route('judge.rounds.entries.scores.store', [$this->round, $this->entry]), [
                'scores' => [
                    $this->pbb->id => ['judging_criteria_id' => $this->pbb->id, 'score' => 55],
                ],
            ])
            ->assertForbidden();

        $this->assertSame('80.000', $sheet->scores()->where('judging_criteria_id', $this->pbb->id)->first()->score);
    }

    public function test_form_renders_read_only_once_submitted(): void
    {
        $sheet = $this->fillAllScores();
        $sheet->status = ScoreSheetStatus::Submitted;
        $sheet->submitted_at = now();
        $sheet->save();

        $response = $this->actingAsJudge()->get(route('judge.rounds.entries.show', [$this->round, $this->entry]));

        $response->assertOk();
        // Real disabled="disabled" attributes, not the "disabled:" Tailwind
        // variant classes on the button component that are always present.
        $response->assertSee('disabled="disabled"', false);
        $response->assertDontSee('Kirim Skor Final');
    }

    public function test_form_is_editable_again_after_a_reopen(): void
    {
        $sheet = $this->fillAllScores();
        $sheet->status = ScoreSheetStatus::Submitted;
        $sheet->submitted_at = now();
        $sheet->save();

        $sheet->status = ScoreSheetStatus::Draft;
        $sheet->submitted_at = null;
        $sheet->save();

        $response = $this->actingAsJudge()->get(route('judge.rounds.entries.show', [$this->round, $this->entry]));

        $response->assertOk();
        $response->assertDontSee('disabled="disabled"', false);
        $response->assertSee('Kirim Skor Final');
    }
}
