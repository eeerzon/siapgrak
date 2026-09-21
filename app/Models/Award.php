<?php

namespace App\Models;

use App\Enums\AwardSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * No HasPublicId — same reasoning as ScoreSheet/CompetitionResult (Sprint 9
 * Keputusan J): nothing routes by public_id, Admin v1 has no public pages
 * (fr-awards-finalization.md Keputusan G). Hard delete allowed (Keputusan H).
 */
class Award extends Model
{
    use HasFactory;

    /**
     * source is deliberately excluded — always 'manual' this sprint
     * (Keputusan I), no form ever sets it. registration_member_id is schema
     * -only, not yet wired to anything (Keputusan F).
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'event_category_id',
        'name',
        'award_type',
        'competition_entry_id',
        'display_order',
    ];

    /**
     * Mirrors the DB column default — see ScoreSheet's identical note.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'source' => 'manual',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => AwardSource::class,
            'display_order' => 'integer',
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
