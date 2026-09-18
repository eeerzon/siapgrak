<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\Status;
use App\Exceptions\InvalidEventStatusTransition;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;

class EventService
{
    /**
     * Allowed status transitions (fr-event-category.md Keputusan B). A target
     * not listed for the current status is rejected with a 422.
     *
     * @var array<string, list<EventStatus>>
     */
    protected const TRANSITIONS = [
        'draft' => [EventStatus::Published, EventStatus::Cancelled],
        'published' => [EventStatus::RegistrationOpen, EventStatus::Draft, EventStatus::Cancelled],
        'registration_open' => [EventStatus::RegistrationClosed, EventStatus::Cancelled],
        'registration_closed' => [EventStatus::RegistrationOpen, EventStatus::Ongoing, EventStatus::Cancelled],
        'ongoing' => [EventStatus::Finished, EventStatus::Cancelled],
        'finished' => [],
        'cancelled' => [EventStatus::Draft],
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $creator, int $organizationId): Event
    {
        $event = new Event($data);
        $event->organization_id = $organizationId;
        $event->created_by = $creator->id;
        $event->status = EventStatus::Draft;
        $event->save();

        return $event;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Event $event, array $data): Event
    {
        $event->update($data);

        return $event;
    }

    /**
     * Statuses the given status may transition to (for building the UI control).
     *
     * @return list<EventStatus>
     */
    public function allowedTransitions(EventStatus $from): array
    {
        return self::TRANSITIONS[$from->value];
    }

    public function changeStatus(Event $event, EventStatus $to): Event
    {
        $from = $event->status;

        if ($from === $to || ! in_array($to, self::TRANSITIONS[$from->value], true)) {
            throw new InvalidEventStatusTransition($from, $to);
        }

        $event->status = $to;

        if ($to === EventStatus::Published && $event->published_at === null) {
            $event->published_at = now();
        }

        $event->save();

        return $event;
    }

    /**
     * Organization IDs the user may create an event under — active
     * memberships only. Mirrors TeamService::assignableOrganizationIds
     * (fr-event-category.md Keputusan F).
     *
     * @return Collection<int, int>
     */
    public function assignableOrganizationIds(User $user): Collection
    {
        return $user->organizations()
            ->wherePivot('status', Status::Active)
            ->pluck('organizations.id');
    }
}
