<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventCategoryRequest;
use App\Http\Requests\Event\UpdateEventCategoryRequest;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Http\RedirectResponse;

/**
 * Categories are displayed on the event overview page (dashboard.events.show),
 * so there is no dedicated index view here — only mutations. Authorization is
 * gated through EventPolicy::manageCategories on the parent {event}
 * (fr-event-category.md Keputusan H — no separate EventCategoryPolicy, same as
 * TeamMemberController has no TeamMemberPolicy).
 */
class EventCategoryController extends Controller
{
    public function store(StoreEventCategoryRequest $request, Event $event): RedirectResponse
    {
        $event->categories()->create([
            ...$request->safe()->except('display_order'),
            'display_order' => $request->integer('display_order'),
        ]);

        return $this->backToEvent($event, 'Kategori berhasil ditambahkan.');
    }

    public function update(UpdateEventCategoryRequest $request, Event $event, EventCategory $category): RedirectResponse
    {
        $this->assertBelongsToEvent($event, $category);

        $category->update([
            ...$request->safe()->except('display_order'),
            'display_order' => $request->integer('display_order', $category->display_order),
        ]);

        return $this->backToEvent($event, 'Kategori berhasil diperbarui.');
    }

    public function destroy(Event $event, EventCategory $category): RedirectResponse
    {
        $this->authorize('manageCategories', $event);
        $this->assertBelongsToEvent($event, $category);

        // A category with registrations is competition history — the FK is
        // RESTRICT, so surface a friendly error instead of a 500
        // (fr-registration.md Keputusan I).
        if ($category->registrations()->exists()) {
            return $this->backToEvent($event, 'Kategori tidak bisa dihapus karena sudah punya pendaftaran.', 'error');
        }

        $category->delete();

        return $this->backToEvent($event, 'Kategori berhasil dihapus.');
    }

    /**
     * Defense against URL manipulation: {category} must belong to {event}
     * from the route — same pattern as TeamMemberController.
     */
    protected function assertBelongsToEvent(Event $event, EventCategory $category): void
    {
        abort_unless($category->event_id === $event->id, 404);
    }

    protected function backToEvent(Event $event, string $message, string $flashKey = 'success'): RedirectResponse
    {
        return redirect()
            ->route('dashboard.events.show', $event)
            ->with($flashKey, $message);
    }
}
