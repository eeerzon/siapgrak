<?php

namespace Tests\Feature\Result;

use App\Enums\CompetitionEntryStatus;
use App\Enums\OrganizationRole;
use App\Enums\ScoreSheetStatus;
use App\Enums\Status;
use App\Models\CompetitionEntry;
use App\Models\CompetitionResult;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Judge;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\Score;
use App\Models\ScoreSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecalculateTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $member;

    protected Organization $organization;

    protected Event $event;

    protected EventCategory $category;

    protected JudgingRound $round;

    protected JudgingCriteria $criteria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->member = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->organization->users()->attach($this->member, ['role' => OrganizationRole::Member, 'status' => Status::Active]);

        $this->event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $this->category = EventCategory::factory()->create(['event_id' => $this->event->id]);
        $this->round = JudgingRound::factory()->create(['event_category_id' => $this->category->id]);
        // weight null -> 100% (Keputusan I) so raw_score == weighted_score,
        // keeping these tests' focus purely on penalty/recalculate behaviour.
        $this->criteria = JudgingCriteria::factory()->create([
            'judging_round_id' => $this->round->id,
            'weight' => null,
            'minimum_score' => 0,
            'maximum_score' => 100,
        ]);
    }

    protected function scoredEntry(float $score, CompetitionEntryStatus $status = CompetitionEntryStatus::Registered): CompetitionEntry
    {
        $entry = CompetitionEntry::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
            'status' => $status,
        ]);

        $judge = Judge::factory()->create();
        $sheet = ScoreSheet::factory()->create([
            'judging_round_id' => $this->round->id,
            'competition_entry_id' => $entry->id,
            'judge_id' => $judge->id,
        ]);
        $sheet->status = ScoreSheetStatus::Submitted;
        $sheet->submitted_at = now();
        $sheet->save();
        Score::factory()->create(['score_sheet_id' => $sheet->id, 'judging_criteria_id' => $this->criteria->id, 'score' => $score]);

        return $entry;
    }

    public function test_owner_can_trigger_calculation(): void
    {
        $entry = $this->scoredEntry(85);

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.results.calculate', $this->round))
            ->assertRedirect(route('dashboard.events.show', $this->event));

        $this->assertDatabaseHas('competition_results', ['competition_entry_id' => $entry->id, 'raw_score' => 85]);
    }

    public function test_recalculate_preserves_a_manually_entered_penalty(): void
    {
        $entry = $this->scoredEntry(85);
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));

        $result = CompetitionResult::where('competition_entry_id', $entry->id)->firstOrFail();
        $this->actingAs($this->owner)->patch(route('dashboard.results.penalty', $result), ['penalty' => 15]);

        // Recalculate again — penalty should survive the delete+rebuild.
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));

        $result = CompetitionResult::where('competition_entry_id', $entry->id)->firstOrFail();
        $this->assertSame('15.000', $result->penalty_score);
        $this->assertSame('70.000', $result->final_score);
    }

    public function test_an_entry_disqualified_after_scoring_disappears_on_recalculate(): void
    {
        $entry = $this->scoredEntry(85);
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));
        $this->assertDatabaseHas('competition_results', ['competition_entry_id' => $entry->id]);

        $entry->status = CompetitionEntryStatus::Disqualified;
        $entry->save();

        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));

        $this->assertDatabaseMissing('competition_results', ['competition_entry_id' => $entry->id]);
    }

    public function test_editing_penalty_updates_final_score_and_reranks(): void
    {
        $first = $this->scoredEntry(90);
        $second = $this->scoredEntry(80);
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));

        $firstResult = CompetitionResult::where('competition_entry_id', $first->id)->firstOrFail();
        $secondResult = CompetitionResult::where('competition_entry_id', $second->id)->firstOrFail();
        $this->assertSame(1, $firstResult->rank);
        $this->assertSame(2, $secondResult->rank);

        // Penalize the leader hard enough to fall behind.
        $this->actingAs($this->owner)->patch(route('dashboard.results.penalty', $firstResult), ['penalty' => 20]);

        $this->assertSame(1, $secondResult->fresh()->rank);
        $this->assertSame(2, $firstResult->fresh()->rank);
    }

    public function test_member_role_cannot_view_calculate_or_edit_penalty(): void
    {
        $entry = $this->scoredEntry(85);
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));
        $result = CompetitionResult::where('competition_entry_id', $entry->id)->firstOrFail();

        $this->actingAs($this->member)
            ->post(route('dashboard.judging-rounds.results.calculate', $this->round))
            ->assertForbidden();

        $this->actingAs($this->member)
            ->patch(route('dashboard.results.penalty', $result), ['penalty' => 5])
            ->assertForbidden();

        $this->actingAs($this->member)
            ->get(route('dashboard.events.show', $this->event))
            ->assertOk()
            ->assertDontSee('Hitung Ulang Hasil');
    }
}
