<?php

namespace App\Models;

use App\Enums\CompetitionEntryStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitionEntry extends Model
{
    use HasFactory, HasPublicId;

    /**
     * Created exclusively by CompetitionService::createEntryForRegistration()
     * from an approved Registration — never mass-assigned from a request.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'event_category_id',
        'registration_id',
        'team_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'performance_order' => 'integer',
            'checked_in_at' => 'datetime',
            'status' => CompetitionEntryStatus::class,
        ];
    }

    protected function publicIdPrefix(): string
    {
        return 'entry_';
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
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
