<?php

namespace Tests\Feature\ActivityLog;

use App\Enums\OrganizationRole;
use App\Enums\RegistrationStatus;
use App\Enums\ScoreSheetStatus;
use App\Enums\Status;
use App\Models\ActivityLog;
use App\Models\Award;
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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoggingTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected Event $event;

    protected EventCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $this->category = EventCategory::factory()->create(['event_id' => $this->event->id]);
    }

    public function test_creating_an_award_writes_an_activity_log(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.events.awards.store', $this->event), [
            'name' => 'Juara 1',
            'award_type' => 'ranking',
        ]);

        $log = ActivityLog::where('action', 'award_created')->firstOrFail();
        $this->assertSame($this->owner->id_user, $log->user_id);
        $this->assertSame($this->event->id, $log->event_id);
        $this->assertSame($this->organization->id, $log->organization_id);
        $this->assertSame('Juara 1', $log->metadata['name']);
    }

    public function test_deleting_an_award_writes_an_activity_log(): void
    {
        $award = Award::factory()->create(['event_id' => $this->event->id, 'name' => 'Best Costume']);

        $this->actingAs($this->owner)->delete(route('dashboard.awards.destroy', $award));

        $this->assertDatabaseHas('activity_logs', ['action' => 'award_deleted']);
        $log = ActivityLog::where('action', 'award_deleted')->firstOrFail();
        $this->assertSame('Best Costume', $log->metadata['name']);
    }

    public function test_activity_log_survives_deletion_of_the_acting_user(): void
    {
        $this->actingAs($this->owner)->post(route('dashboard.events.awards.store', $this->event), [
            'name' => 'Juara 1',
            'award_type' => 'ranking',
        ]);
        $log = ActivityLog::where('action', 'award_created')->firstOrFail();

        $this->owner->delete();

        $log->refresh();
        $this->assertNull($log->user_id);
        $this->assertSame('award_created', $log->action);
    }

    public function test_approving_a_registration_writes_an_activity_log(): void
    {
        $registration = Registration::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
            'status' => RegistrationStatus::Submitted,
        ]);

        $this->actingAs($this->owner)->patch(
            route('dashboard.events.registrations.status', [$this->event, $registration]),
            ['status' => 'approved'],
        );

        $log = ActivityLog::where('action', 'registration_approved')->firstOrFail();
        $this->assertSame($this->owner->id_user, $log->user_id);
        $this->assertSame($this->event->id, $log->event_id);
        $this->assertSame(Registration::class, $log->subject_type);
        $this->assertSame($registration->id, $log->subject_id);
    }

    public function test_rejecting_a_registration_writes_an_activity_log_with_the_review_notes(): void
    {
        $registration = Registration::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
            'status' => RegistrationStatus::Submitted,
        ]);

        $this->actingAs($this->owner)->patch(
            route('dashboard.events.registrations.status', [$this->event, $registration]),
            ['status' => 'rejected', 'review_notes' => 'Dokumen tidak lengkap'],
        );

        $log = ActivityLog::where('action', 'registration_rejected')->firstOrFail();
        $this->assertSame('Dokumen tidak lengkap', $log->metadata['review_notes']);
    }

    public function test_judge_submitting_a_score_sheet_writes_an_activity_log_without_a_user(): void
    {
        $round = JudgingRound::factory()->create(['event_category_id' => $this->category->id]);
        $criteria = JudgingCriteria::factory()->create([
            'judging_round_id' => $round->id,
            'minimum_score' => 0,
            'maximum_score' => 100,
        ]);
        $judge = Judge::factory()->create(['organization_id' => $this->organization->id]);
        JudgeAssignment::factory()->create(['judging_round_id' => $round->id, 'judge_id' => $judge->id]);
        $entry = CompetitionEntry::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
        ]);
        $sheet = ScoreSheet::factory()->create([
            'judging_round_id' => $round->id,
            'competition_entry_id' => $entry->id,
            'judge_id' => $judge->id,
        ]);
        Score::factory()->create(['score_sheet_id' => $sheet->id, 'judging_criteria_id' => $criteria->id, 'score' => 90]);

        $this->withSession(['judge_id' => $judge->id])
            ->post(route('judge.rounds.entries.submit', [$round, $entry]));

        $log = ActivityLog::where('action', 'score_sheet_submitted')->firstOrFail();
        $this->assertNull($log->user_id);
        $this->assertSame($judge->id, $log->metadata['judge_id']);
        $this->assertSame($this->event->id, $log->event_id);
    }

    public function test_organizer_reopening_a_score_sheet_writes_an_activity_log(): void
    {
        $round = JudgingRound::factory()->create(['event_category_id' => $this->category->id]);
        $judge = Judge::factory()->create(['organization_id' => $this->organization->id]);
        $entry = CompetitionEntry::factory()->create([
            'event_id' => $this->event->id,
            'event_category_id' => $this->category->id,
        ]);
        $sheet = ScoreSheet::factory()->create([
            'judging_round_id' => $round->id,
            'competition_entry_id' => $entry->id,
            'judge_id' => $judge->id,
        ]);
        $sheet->status = ScoreSheetStatus::Submitted;
        $sheet->submitted_at = now();
        $sheet->save();

        $this->actingAs($this->owner)->post(route('dashboard.score-sheets.reopen', $sheet), ['reason' => 'Salah input']);

        $log = ActivityLog::where('action', 'score_sheet_reopened')->firstOrFail();
        $this->assertSame($this->owner->id_user, $log->user_id);
        $this->assertSame('Salah input', $log->metadata['reason']);
    }

    public function test_calculating_results_writes_an_activity_log(): void
    {
        $round = JudgingRound::factory()->create(['event_category_id' => $this->category->id]);

        $this->actingAs($this->owner)->post(route('dashboard.judging-rounds.results.calculate', $round));

        $log = ActivityLog::where('action', 'result_recalculated')->firstOrFail();
        $this->assertSame($this->owner->id_user, $log->user_id);
        $this->assertSame($round->id, $log->metadata['judging_round_id']);
    }
}
