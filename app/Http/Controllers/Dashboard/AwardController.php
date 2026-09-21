<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Award\StoreAwardRequest;
use App\Http\Requests\Award\UpdateAwardRequest;
use App\Models\Award;
use App\Models\Event;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;

/**
 * Managed inline on the event's "Hasil" tab (no dedicated index view) —
 * same pattern as EventCategoryController. Gated through
 * EventPolicy::manageResults, no separate AwardPolicy
 * (fr-awards-finalization.md Keputusan D).
 */
class AwardController extends Controller
{
    public function __construct(protected ActivityLogger $activityLogger) {}

    public function store(StoreAwardRequest $request, Event $event): RedirectResponse
    {
        $award = $event->awards()->create($request->validated());

        $this->activityLogger->log('award_created', $event, $award, ['name' => $award->name]);

        return $this->backToEvent($event, 'Penghargaan berhasil ditambahkan.');
    }

    public function update(UpdateAwardRequest $request, Award $award): RedirectResponse
    {
        $award->update($request->validated());

        $this->activityLogger->log('award_updated', $award->event, $award, ['name' => $award->name]);

        return $this->backToEvent($award->event, 'Penghargaan berhasil diperbarui.');
    }

    public function destroy(Award $award): RedirectResponse
    {
        $this->authorize('manageResults', $award->event);

        $event = $award->event;
        $name = $award->name;
        $award->delete();

        $this->activityLogger->log('award_deleted', $event, null, ['name' => $name]);

        return $this->backToEvent($event, 'Penghargaan dihapus.');
    }

    protected function backToEvent(Event $event, string $message): RedirectResponse
    {
        return redirect()
            ->route('dashboard.events.show', $event)
            ->with('success', $message);
    }
}
