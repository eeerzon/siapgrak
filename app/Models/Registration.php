<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Registration extends Model
{
    use HasFactory, HasPublicId;

    /**
     * team_id + event_category_id are fixed at create. status / submitted_by /
     * submitted_at / reviewed_* are set by RegistrationService, never from
     * request input (fr-registration.md RULE-4, RULE-5).
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'event_category_id',
        'team_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'status' => RegistrationStatus::class,
        ];
    }

    protected function publicIdPrefix(): string
    {
        return 'reg_';
    }

    /**
     * Roster and documents may only be changed while the registration is a
     * draft or has revision requested (fr-registration.md RULE-6).
     */
    public function isEditable(): bool
    {
        return in_array($this->status, [
            RegistrationStatus::Draft,
            RegistrationStatus::RevisionRequested,
        ], true);
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
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return HasMany<RegistrationMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(RegistrationMember::class)->orderBy('id');
    }

    /**
     * @return HasMany<RegistrationDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class)->orderBy('id');
    }

    /**
     * @return HasOne<CompetitionEntry, $this>
     */
    public function entry(): HasOne
    {
        return $this->hasOne(CompetitionEntry::class);
    }
}
