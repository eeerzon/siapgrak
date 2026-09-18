<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, HasPublicId;

    /**
     * Mass-assignable. status / organization_id / created_by / published_at
     * are set by EventService, never from request input (fr-event-category.md).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'poster_path',
        'start_date',
        'end_date',
        'registration_start_at',
        'registration_end_at',
        'venue_name',
        'venue_address',
        'province_code',
        'city_code',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_start_at' => 'datetime',
            'registration_end_at' => 'datetime',
            'published_at' => 'datetime',
            'status' => EventStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->name);
            }
        });
    }

    protected function publicIdPrefix(): string
    {
        return 'evt_';
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<EventCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(EventCategory::class)->orderBy('display_order')->orderBy('id');
    }

    /**
     * @return HasMany<EventStaff, $this>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(EventStaff::class);
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
        return $this->hasMany(CompetitionEntry::class);
    }

    /**
     * @return HasMany<EventScheduleSlot, $this>
     */
    public function scheduleSlots(): HasMany
    {
        return $this->hasMany(EventScheduleSlot::class)->orderBy('display_order');
    }

    /**
     * @return HasMany<Award, $this>
     */
    public function awards(): HasMany
    {
        return $this->hasMany(Award::class)->orderBy('display_order');
    }
}
