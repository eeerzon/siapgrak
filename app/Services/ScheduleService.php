<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventScheduleSlot;

class ScheduleService
{
    /**
     * New slots always land at the end of the event's timeline
     * (fr-schedule.md RULE-3) — reordering happens via moveUp()/moveDown().
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Event $event, array $data): EventScheduleSlot
    {
        $nextOrder = $event->scheduleSlots()->max('display_order');

        return $event->scheduleSlots()->create([
            ...$data,
            'display_order' => $nextOrder === null ? 0 : $nextOrder + 1,
        ]);
    }

    /**
     * Every field stays editable (fr-schedule.md Keputusan A) — unlike Event/
     * Registration, a slot isn't an identity resource.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(EventScheduleSlot $slot, array $data): EventScheduleSlot
    {
        $slot->update($data);

        return $slot;
    }

    public function moveUp(EventScheduleSlot $slot): void
    {
        // Query the model directly, NOT $event->scheduleSlots() — that relation
        // bakes in its own orderBy('display_order') ASC, and Eloquent/query
        // builder orderBy() calls are additive, not a replacement. Chaining
        // orderByDesc() on top of it does not override the ASC already set,
        // so `first()` would silently return the smallest match instead of
        // the true adjacent one. Bug found + fixed 2026-09-14, see fr-schedule.md.
        $previous = EventScheduleSlot::query()
            ->where('event_id', $slot->event_id)
            ->where('display_order', '<', $slot->display_order)
            ->orderByDesc('display_order')
            ->first();

        $this->swapOrder($slot, $previous);
    }

    public function moveDown(EventScheduleSlot $slot): void
    {
        $next = EventScheduleSlot::query()
            ->where('event_id', $slot->event_id)
            ->where('display_order', '>', $slot->display_order)
            ->orderBy('display_order')
            ->first();

        $this->swapOrder($slot, $next);
    }

    protected function swapOrder(EventScheduleSlot $slot, ?EventScheduleSlot $neighbor): void
    {
        if ($neighbor === null) {
            return;
        }

        [$slotOrder, $neighborOrder] = [$slot->display_order, $neighbor->display_order];

        $slot->update(['display_order' => $neighborOrder]);
        $neighbor->update(['display_order' => $slotOrder]);
    }
}
