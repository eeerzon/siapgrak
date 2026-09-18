<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventStaffRequest;
use App\Http\Requests\Event\UpdateEventStaffRequest;
use App\Models\Event;
use App\Models\EventStaff;
use Illuminate\Http\RedirectResponse;

/**
 * Staff are shown on the event overview page (dashboard.events.show) — only
 * mutations here. Gated through EventPolicy::manageStaff on the parent {event}
 * (fr-event-category.md Keputusan H). event_staff.role is recorded but is NOT
 * yet an authorization source (Keputusan A).
 */
class EventStaffController extends Controller
{
    public function store(StoreEventStaffRequest $request, Event $event): RedirectResponse
    {
        $event->staff()->create($request->validated());

        return $this->backToEvent($event, 'Staff berhasil ditambahkan.');
    }

    public function update(UpdateEventStaffRequest $request, Event $event, EventStaff $staff): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $staff);

        $staff->update($request->validated());

        return $this->backToEvent($event, 'Peran staff berhasil diperbarui.');
    }

    public function destroy(Event $event, EventStaff $staff): RedirectResponse
    {
        $this->authorize('manageStaff', $event);
        $this->assertBelongsToEvent($event, $staff);

        $staff->delete();

        return $this->backToEvent($event, 'Staff berhasil dihapus.');
    }

    protected function assertBelongsToEvent(Event $event, EventStaff $staff): void
    {
        abort_unless($staff->event_id === $event->id, 404);
    }

    protected function backToEvent(Event $event, string $message): RedirectResponse
    {
        return redirect()
            ->route('dashboard.events.show', $event)
            ->with('success', $message);
    }
}
