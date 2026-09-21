<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreEventScheduleSlotRequest;
use App\Http\Requests\Schedule\UpdateEventScheduleSlotRequest;
use App\Models\Event;
use App\Models\EventScheduleSlot;
use App\Services\ScheduleService;
use Illuminate\Http\RedirectResponse;

/**
 * Unlike Event/Registration/CompetitionEntry, a schedule slot is pure
 * logistics — every field stays editable and hard delete is allowed
 * (fr-schedule.md Keputusan A/B). Gated through EventPolicy::manageSchedule,
 * no separate EventScheduleSlotPolicy (same pattern as prior sprints).
 */
class EventScheduleSlotController extends Controller
{
    public function __construct(protected ScheduleService $schedule) {}

    public function store(StoreEventScheduleSlotRequest $request, Event $event): RedirectResponse
    {
        $this->schedule->create($event, $request->validated());

        return $this->backToEvent($event, 'Slot jadwal ditambahkan.');
    }

    public function update(UpdateEventScheduleSlotRequest $request, Event $event, EventScheduleSlot $slot): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $slot);

        $this->schedule->update($slot, $request->validated());

        return $this->backToEvent($event, 'Slot jadwal diperbarui.');
    }

    public function destroy(Event $event, EventScheduleSlot $slot): RedirectResponse
    {
        $this->authorize('manageSchedule', $event);
        $this->assertBelongsToEvent($event, $slot);

        $slot->delete();

        return $this->backToEvent($event, 'Slot jadwal dihapus.');
    }

    public function moveUp(Event $event, EventScheduleSlot $slot): RedirectResponse
    {
        $this->authorize('manageSchedule', $event);
        $this->assertBelongsToEvent($event, $slot);

        $this->schedule->moveUp($slot);

        return $this->backToEvent($event, 'Urutan slot diperbarui.');
    }

    public function moveDown(Event $event, EventScheduleSlot $slot): RedirectResponse
    {
        $this->authorize('manageSchedule', $event);
        $this->assertBelongsToEvent($event, $slot);

        $this->schedule->moveDown($slot);

        return $this->backToEvent($event, 'Urutan slot diperbarui.');
    }

    protected function assertBelongsToEvent(Event $event, EventScheduleSlot $slot): void
    {
        abort_unless($slot->event_id === $event->id, 404);
    }

    protected function backToEvent(Event $event, string $message): RedirectResponse
    {
        return redirect()
            ->route('dashboard.events.show', $event)
            ->with('success', $message);
    }
}
