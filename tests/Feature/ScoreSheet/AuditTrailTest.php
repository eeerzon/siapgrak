<?php

namespace Tests\Feature\ScoreSheet;

use App\Enums\OrganizationRole;
use App\Enums\ScoreSheetStatus;
use App\Enums\Status;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Judge;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\ScoreSheet;
use App\Models\ScoreSheetRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Constitution: "Deleted user tidak boleh merusak audit trail" — verified
 * directly here, not just implied by the FK definition.
 */
class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected JudgingRound $round;

    protected Judge $judge;

    protected CompetitionEntry $entry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);

        $event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $category = EventCategory::factory()->create(['event_id' => $event->id]);
        $this->round = JudgingRound::factory()->create(['event_category_id' => $category->id]);
        $this->judge = Judge::factory()->create(['organization_id' => $this->organization->id]);
        $this->entry = CompetitionEntry::factory()->create([
            'event_id' => $event->id,
            'event_category_id' => $category->id,
        ]);
    }

    protected function submittedSheet(): ScoreSheet
    {
        $sheet = ScoreSheet::factory()->create([
            'judging_round_id' => $this->round->id,
            'competition_entry_id' => $this->entry->id,
            'judge_id' => $this->judge->id,
        ]);
        $sheet->status = ScoreSheetStatus::Submitted;
        $sheet->submitted_at = now();
        $sheet->save();

        return $sheet;
    }

    public function test_two_reopens_create_two_independent_revision_rows(): void
    {
        $sheet = $this->submittedSheet();

        $this->actingAs($this->owner)->post(route('dashboard.score-sheets.reopen', $sheet), ['reason' => 'Koreksi pertama']);

        // Judge resubmits so it can be reopened a second time.
        $sheet->refresh();
        $sheet->status = ScoreSheetStatus::Submitted;
        $sheet->submitted_at = now();
        $sheet->save();

        $this->actingAs($this->owner)->post(route('dashboard.score-sheets.reopen', $sheet), ['reason' => 'Koreksi kedua']);

        $this->assertSame(2, ScoreSheetRevision::where('score_sheet_id', $sheet->id)->count());
        $this->assertSame(2, $sheet->fresh()->revision_count);
        $this->assertDatabaseHas('score_sheet_revisions', ['reason' => 'Koreksi pertama']);
        $this->assertDatabaseHas('score_sheet_revisions', ['reason' => 'Koreksi kedua']);
    }

    public function test_revision_row_survives_deletion_of_the_requesting_user(): void
    {
        $sheet = $this->submittedSheet();

        $this->actingAs($this->owner)->post(route('dashboard.score-sheets.reopen', $sheet), ['reason' => 'Koreksi']);

        $revision = ScoreSheetRevision::where('score_sheet_id', $sheet->id)->firstOrFail();
        $this->assertSame($this->owner->id_user, $revision->requested_by);

        $this->owner->delete();

        $revision->refresh();
        $this->assertNull($revision->requested_by);
        $this->assertSame('Koreksi', $revision->reason);
    }

    public function test_revisions_are_shown_newest_first_on_the_detail_page(): void
    {
        $sheet = $this->submittedSheet();
        $this->actingAs($this->owner)->post(route('dashboard.score-sheets.reopen', $sheet), ['reason' => 'Alasan A']);

        $sheet->refresh();
        $sheet->status = ScoreSheetStatus::Submitted;
        $sheet->submitted_at = now();
        $sheet->save();
        $this->actingAs($this->owner)->post(route('dashboard.score-sheets.reopen', $sheet), ['reason' => 'Alasan B']);

        $response = $this->actingAs($this->owner)->get(route('dashboard.score-sheets.show', $sheet));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertTrue(strpos($content, 'Alasan B') < strpos($content, 'Alasan A'));
    }
}
