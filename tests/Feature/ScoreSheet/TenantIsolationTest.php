<?php

namespace Tests\Feature\ScoreSheet;

use App\Enums\OrganizationRole;
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
use App\Models\ScoreSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The single most sensitive domain in the app (CLAUDE.md Constitution) —
 * a judge must never see or write another org's round/entry/criteria via
 * direct URL manipulation, even without any assignment overlap by accident.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Judge $judgeA;

    protected JudgingRound $roundB;

    protected JudgingCriteria $criteriaB;

    protected CompetitionEntry $entryB;

    protected function setUp(): void
    {
        parent::setUp();

        $orgA = Organization::factory()->create();
        $this->judgeA = Judge::factory()->create(['organization_id' => $orgA->id]);

        $orgB = Organization::factory()->create();
        $eventB = Event::factory()->create(['organization_id' => $orgB->id]);
        $categoryB = EventCategory::factory()->create(['event_id' => $eventB->id]);
        $this->roundB = JudgingRound::factory()->create(['event_category_id' => $categoryB->id]);
        $this->criteriaB = JudgingCriteria::factory()->create(['judging_round_id' => $this->roundB->id]);
        $this->entryB = CompetitionEntry::factory()->create([
            'event_id' => $eventB->id,
            'event_category_id' => $categoryB->id,
        ]);
    }

    protected function actingAsJudgeA(): static
    {
        $this->withSession(['judge_id' => $this->judgeA->id]);

        return $this;
    }

    public function test_judge_a_cannot_list_entries_of_round_b_without_an_assignment(): void
    {
        $this->actingAsJudgeA()
            ->get(route('judge.rounds.entries.index', $this->roundB))
            ->assertForbidden();
    }

    public function test_judge_a_cannot_open_a_scoring_form_for_round_b(): void
    {
        $this->actingAsJudgeA()
            ->get(route('judge.rounds.entries.show', [$this->roundB, $this->entryB]))
            ->assertForbidden();
    }

    public function test_judge_a_cannot_save_scores_against_round_b(): void
    {
        $this->actingAsJudgeA()
            ->post(route('judge.rounds.entries.scores.store', [$this->roundB, $this->entryB]), [
                'scores' => [
                    $this->criteriaB->id => ['judging_criteria_id' => $this->criteriaB->id, 'score' => 50],
                ],
            ])
            ->assertSessionHasErrors('scores');

        $this->assertDatabaseMissing('scores', ['judging_criteria_id' => $this->criteriaB->id]);
    }

    /**
     * Even WITH an assignment record elsewhere, judge A's session must never
     * let them touch round B's data — assignment existing for judge A on a
     * DIFFERENT round doesn't grant access to round B.
     */
    public function test_judge_a_with_an_unrelated_assignment_still_cannot_touch_round_b(): void
    {
        $ownRound = JudgingRound::factory()->create();
        JudgeAssignment::factory()->create(['judging_round_id' => $ownRound->id, 'judge_id' => $this->judgeA->id]);

        $this->actingAsJudgeA()
            ->get(route('judge.rounds.entries.index', $this->roundB))
            ->assertForbidden();
    }

    public function test_judge_a_cannot_submit_against_round_b(): void
    {
        $this->actingAsJudgeA()
            ->post(route('judge.rounds.entries.submit', [$this->roundB, $this->entryB]))
            ->assertForbidden();
    }

    /**
     * Organizer side (Sprint 10): an owner of org A must never manage
     * (view/lock/reopen) a score sheet that belongs to org B's event.
     */
    public function test_organizer_of_org_a_cannot_manage_a_score_sheet_of_org_b(): void
    {
        $ownerA = User::factory()->create();
        $orgA = Organization::factory()->create();
        $orgA->users()->attach($ownerA, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $judgeB = Judge::factory()->create(['organization_id' => $this->roundB->category->event->organization_id]);
        $sheetB = ScoreSheet::factory()->create([
            'judging_round_id' => $this->roundB->id,
            'competition_entry_id' => $this->entryB->id,
            'judge_id' => $judgeB->id,
        ]);
        $sheetB->status = ScoreSheetStatus::Submitted;
        $sheetB->submitted_at = now();
        $sheetB->save();

        $this->actingAs($ownerA)->get(route('dashboard.score-sheets.show', $sheetB))->assertForbidden();
        $this->actingAs($ownerA)->post(route('dashboard.score-sheets.lock', $sheetB))->assertForbidden();
        $this->actingAs($ownerA)
            ->post(route('dashboard.score-sheets.reopen', $sheetB), ['reason' => 'x'])
            ->assertForbidden();
    }
}
