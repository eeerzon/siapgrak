<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory, HasPublicId;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'short_name',
        'logo_path',
        'province_code',
        'city_code',
        'description',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $team): void {
            if (empty($team->slug)) {
                $team->slug = Str::slug($team->name);
            }
        });
    }

    protected function publicIdPrefix(): string
    {
        return 'team_';
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<TeamMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
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
}
