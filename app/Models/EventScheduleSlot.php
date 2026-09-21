<?php

namespace App\Models;

use App\Enums\ScheduleSlotType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A living-document logistics row — unlike Event/Registration/CompetitionEntry,
 * every field here stays editable after creation and hard delete is allowed
 * (fr-schedule.md Keputusan A/B). No public_id — sub-resource of Event, same
 * as EventCategory/EventStaff.
 */
class EventScheduleSlot extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'event_category_id',
        'competition_entry_id',
        'title',
        'start_at',
        'end_at',
        'venue_area',
        'slot_type',
        'display_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'display_order' => 'integer',
            'slot_type' => ScheduleSlotType::class,
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<EventCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    /**
     * @return BelongsTo<CompetitionEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(CompetitionEntry::class, 'competition_entry_id');
    }
}
