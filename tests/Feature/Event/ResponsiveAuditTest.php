<?php

namespace Tests\Feature\Event;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventScheduleSlot;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F12 — adversarial responsive/UX audit findings (frontend-plan.md §Page
 * Inventory). No visual browser tool is available in this environment, so
 * this is a structural audit: missing body scroll-lock behind an open
 * drawer/modal, touch targets sitting under a comfortable tap size, and a
 * handful of form field grids in F7/F9's own drawers that broke the
 * project's own "form multi-kolom -> 1 kolom di mobile" rule (everywhere
 * else in the app already follows it correctly). These assertions check the
 * rendered HTML carries the fix, not actual browser rendering.
 */
class ResponsiveAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Organization $organization;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->users()->attach($this->owner, ['role' => OrganizationRole::Owner, 'status' => Status::Active]);
        $this->event = Event::factory()->create(['organization_id' => $this->organization->id]);
    }

    protected function eventPage()
    {
        return $this->actingAs($this->owner)->get(route('dashboard.events.show', $this->event));
    }

    public function test_drawers_lock_background_scroll_while_open(): void
    {
        EventCategory::factory()->create(['event_id' => $this->event->id]);

        $this->eventPage()->assertSee(
            "x-effect=\"document.body.classList.toggle('overflow-hidden', open)\"",
            false,
        );
    }

    public function test_drawer_close_button_has_an_enlarged_tap_target(): void
    {
        EventCategory::factory()->create(['event_id' => $this->event->id]);

        $this->eventPage()->assertSee('class="-m-2 cursor-pointer p-2 text-slate-400 hover:text-slate-600" aria-label="Tutup"', false);
    }

    public function test_schedule_reorder_buttons_use_a_comfortable_tap_target(): void
    {
        EventScheduleSlot::factory()->create(['event_id' => $this->event->id]);

        $response = $this->eventPage();

        $response->assertDontSee('cursor-pointer rounded p-1 text-slate-400', false);
        $response->assertSee('cursor-pointer rounded p-2 text-slate-400', false);
    }

    public function test_category_drawer_paired_fields_stack_on_mobile(): void
    {
        EventCategory::factory()->create(['event_id' => $this->event->id]);

        $response = $this->eventPage();

        $response->assertDontSee('class="grid grid-cols-2 gap-3"', false);
        $response->assertSee('class="grid grid-cols-1 gap-3 sm:grid-cols-2"', false);
    }
}
