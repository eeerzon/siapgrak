<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCategory extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'quota',
        'min_members',
        'max_members',
        'registration_start_at',
        'registration_end_at',
        'status',
        'display_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quota' => 'integer',
            'min_members' => 'integer',
            'max_members' => 'integer',
            'display_order' => 'integer',
            'registration_start_at' => 'datetime',
            'registration_end_at' => 'datetime',
            'status' => Status::class,
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
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * @return HasMany<CompetitionEntry, $this>
     */
    public function competitionEntries(): HasMany
    {
        return $this->hasMany(CompetitionEntry::class, 'event_category_id')->orderBy('competition_number');
    }

    /**
     * @return HasMany<JudgingRound, $this>
     */
    public function judgingRounds(): HasMany
    {
        return $this->hasMany(JudgingRound::class, 'event_category_id')->orderBy('display_order');
    }
}
