<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreEventCategoryRequest;
use App\Http\Requests\Event\UpdateEventCategoryRequest;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Http\RedirectResponse;

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

        if ($category->registrations()->exists()) {
            return $this->backToEvent($event, 'Kategori tidak bisa dihapus karena sudah punya pendaftaran.', 'error');
        }

        $category->delete();

        return $this->backToEvent($event, 'Kategori berhasil dihapus.');
    }

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
