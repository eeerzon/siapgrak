<?php

namespace Tests\Feature\Event;

use App\Enums\OrganizationRole;
use App\Enums\RegistrationStatus;
use App\Enums\ScoreSheetStatus;
use App\Enums\Status;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventScheduleSlot;
use App\Models\Judge;
use App\Models\JudgeAssignment;
use App\Models\JudgingRound;
use App\Models\Organization;
use App\Models\Registration;
use App\Models\ScoreSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F5 — Event Workspace Overview readiness summary + Next Action
 * (frontend-plan.md §Event Workspace).
 */
class OverviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
    }

    public function test_readiness_strip_reflects_a_brand_new_event(): void
    {
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $event));

        $readiness = $response->viewData('readiness');
        $byLabel = collect($readiness)->keyBy('label');

        $this->assertSame('0', $byLabel['Kategori']['value']);
        $this->assertFalse($byLabel['Kategori']['ok']);
        $this->assertSame('0 slot', $byLabel['Jadwal']['value']);
        $this->assertFalse($byLabel['Jadwal']['ok']);
        $this->assertSame('Belum mulai', $byLabel['Penilaian']['value']);
        $this->assertFalse($byLabel['Penilaian']['ok']);
    }

    public function test_readiness_strip_reflects_a_fully_set_up_event(): void
    {
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $category = EventCategory::factory()->create(['event_id' => $event->id]);
        EventScheduleSlot::factory()->create(['event_id' => $event->id]);

        $round = JudgingRound::factory()->create(['event_category_id' => $category->id]);
        $judge = Judge::factory()->create(['organization_id' => $this->organization->id]);
        JudgeAssignment::factory()->create(['judging_round_id' => $round->id, 'judge_id' => $judge->id]);

        $entry = CompetitionEntry::factory()->create(['event_id' => $event->id, 'event_category_id' => $category->id]);
        ScoreSheet::factory()->create([
            'judging_round_id' => $round->id,
            'competition_entry_id' => $entry->id,
            'judge_id' => $judge->id,
            'status' => ScoreSheetStatus::Locked,
        ]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $event));

        $byLabel = collect($response->viewData('readiness'))->keyBy('label');

        $this->assertSame('1', $byLabel['Kategori']['value']);
        $this->assertTrue($byLabel['Kategori']['ok']);
        $this->assertSame('1 slot', $byLabel['Jadwal']['value']);
        $this->assertTrue($byLabel['Jadwal']['ok']);
        $this->assertSame('1', $byLabel['Juri']['value']);
        $this->assertTrue($byLabel['Juri']['ok']);
        $this->assertSame('1/1 selesai', $byLabel['Penilaian']['value']);
        $this->assertTrue($byLabel['Penilaian']['ok']);
    }

    public function test_next_action_prioritizes_pending_registrations_over_everything_else(): void
    {
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $category = EventCategory::factory()->create(['event_id' => $event->id]);
        Registration::factory()->create([
            'event_id' => $event->id,
            'event_category_id' => $category->id,
            'status' => RegistrationStatus::Submitted,
        ]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $event));

        $nextAction = $response->viewData('nextAction');
        $this->assertSame('participants', $nextAction['tab']);
        $this->assertStringContainsString('1 registrasi menunggu direview', $nextAction['message']);
    }

    public function test_next_action_prompts_for_categories_when_none_exist(): void
    {
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $event));

        $this->assertSame('categories', $response->viewData('nextAction')['tab']);
    }

    public function test_next_action_prompts_for_schedule_once_categories_exist(): void
    {
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);
        EventCategory::factory()->create(['event_id' => $event->id]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $event));

        $this->assertSame('schedule', $response->viewData('nextAction')['tab']);
    }

    public function test_next_action_prompts_for_judges_once_rounds_exist_without_assignments(): void
    {
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $category = EventCategory::factory()->create(['event_id' => $event->id]);
        EventScheduleSlot::factory()->create(['event_id' => $event->id]);
        JudgingRound::factory()->create(['event_category_id' => $category->id]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $event));

        $this->assertSame('judging', $response->viewData('nextAction')['tab']);
    }

    public function test_next_action_is_null_when_everything_is_ready(): void
    {
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $category = EventCategory::factory()->create(['event_id' => $event->id]);
        EventScheduleSlot::factory()->create(['event_id' => $event->id]);
        $round = JudgingRound::factory()->create(['event_category_id' => $category->id]);
        $judge = Judge::factory()->create(['organization_id' => $this->organization->id]);
        JudgeAssignment::factory()->create(['judging_round_id' => $round->id, 'judge_id' => $judge->id]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $event));

        $this->assertNull($response->viewData('nextAction'));
        $response->assertDontSee('Next Action');
    }

    public function test_registration_breakdown_only_shows_once_a_registration_window_is_set(): void
    {
        $event = Event::factory()->create([
            'organization_id' => $this->organization->id,
            'registration_start_at' => null,
            'registration_end_at' => null,
        ]);

        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $event));
        $response->assertDontSee('Pendaftaran:');

        $event->update(['registration_start_at' => now(), 'registration_end_at' => now()->addWeek()]);
        $response = $this->actingAs($this->owner)->get(route('dashboard.events.show', $event));
        $response->assertSee('Pendaftaran:');
    }
}
