<?php

namespace Tests\Feature\Result;

use App\Enums\OrganizationRole;
use App\Enums\ScoreSheetStatus;
use App\Enums\Status;
use App\Models\ActivityLog;
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

class FinalizeTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $admin;

    protected Organization $organization;

    protected Event $event;

    protected JudgingRound $round;

    protected JudgingCriteria $criteria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->organization->users()->attach($this->admin, ['role' => OrganizationRole::Admin, 'status' => Status::Active]);

        $this->event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $category = EventCategory::factory()->create(['event_id' => $this->event->id]);
        $this->round = JudgingRound::factory()->create(['event_category_id' => $category->id]);
        $this->criteria = JudgingCriteria::factory()->create([
            'judging_round_id' => $this->round->id,
            'weight' => null,
            'minimum_score' => 0,
            'maximum_score' => 100,
        ]);

        $entry = CompetitionEntry::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $category->id,
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
        Score::factory()->create(['score_sheet_id' => $sheet->id, 'judging_criteria_id' => $this->criteria->id, 'score' => 85]);
    }

    public function test_finalize_fails_when_no_results_exist_yet(): void
    {
        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.results.finalize', $this->round))
            ->assertSessionHasErrors('finalize');
    }

    public function test_finalize_succeeds_once_results_exist(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.results.finalize', $this->round))
            ->assertRedirect(route('dashboard.events.show', $this->event));

        $result = CompetitionResult::where('judging_round_id', $this->round->id)->firstOrFail();
        $this->assertSame('finalized', $result->status->value);
        $this->assertNotNull($result->finalized_at);
    }

    public function test_finalize_twice_is_rejected(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.finalize', $this->round));

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.results.finalize', $this->round))
            ->assertSessionHasErrors('finalize');
    }

    public function test_recalculate_is_blocked_once_finalized(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.finalize', $this->round));

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.results.calculate', $this->round))
            ->assertSessionHasErrors('calculate');
    }

    public function test_publish_requires_finalized_first(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.results.publish', $this->round))
            ->assertSessionHasErrors('publish');
    }

    public function test_publish_succeeds_from_finalized(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.finalize', $this->round));

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.results.publish', $this->round))
            ->assertRedirect(route('dashboard.events.show', $this->event));

        $result = CompetitionResult::where('judging_round_id', $this->round->id)->firstOrFail();
        $this->assertSame('published', $result->status->value);
    }

    public function test_admin_can_reopen_a_finalized_round_with_reason(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.finalize', $this->round));

        $this->actingAs($this->admin)
            ->post(route('dashboard.judging-rounds.results.reopen', $this->round), ['reason' => 'Salah hitung'])
            ->assertRedirect(route('dashboard.events.show', $this->event));

        $result = CompetitionResult::where('judging_round_id', $this->round->id)->firstOrFail();
        $this->assertSame('draft', $result->status->value);
        $this->assertNull($result->finalized_at);
    }

    public function test_admin_cannot_reopen_a_published_round(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.finalize', $this->round));
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.publish', $this->round));

        $this->actingAs($this->admin)
            ->post(route('dashboard.judging-rounds.results.reopen', $this->round), ['reason' => 'Salah hitung'])
            ->assertForbidden();
    }

    public function test_owner_can_reopen_a_published_round(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.finalize', $this->round));
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.publish', $this->round));

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.results.reopen', $this->round), ['reason' => 'Salah hitung'])
            ->assertRedirect(route('dashboard.events.show', $this->event));

        $result = CompetitionResult::where('judging_round_id', $this->round->id)->firstOrFail();
        $this->assertSame('draft', $result->status->value);
    }

    public function test_reopen_without_reason_is_rejected(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.finalize', $this->round));

        $this->actingAs($this->owner)
            ->post(route('dashboard.judging-rounds.results.reopen', $this->round), [])
            ->assertSessionHasErrors('reason');
    }

    public function test_score_survives_a_reopen(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.finalize', $this->round));

        $result = CompetitionResult::where('judging_round_id', $this->round->id)->firstOrFail();
        $rawBefore = $result->raw_score;

        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.reopen', $this->round), ['reason' => 'x']);

        $result->refresh();
        $this->assertSame($rawBefore, $result->raw_score);
    }

    public function test_finalize_and_reopen_write_activity_log_entries(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $this->round));
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.finalize', $this->round));
        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.reopen', $this->round), ['reason' => 'Koreksi']);

        $this->assertDatabaseHas('activity_logs', ['action' => 'result_finalized', 'user_id' => $this->owner->id_user, 'event_id' => $this->event->id]);

        $reopenLog = ActivityLog::where('action', 'result_reopened')->firstOrFail();
        $this->assertSame($this->owner->id_user, $reopenLog->user_id);
        $this->assertSame('Koreksi', $reopenLog->metadata['reason']);
    }
}
