<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitionCategory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['competition_listing_id', 'name', 'display_order'];

    /**
     * @return BelongsTo<CompetitionListing, $this>
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(CompetitionListing::class, 'competition_listing_id');
    }

    /**
     * @return HasMany<CompetitionParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(CompetitionParticipant::class)->orderBy('performance_order')->orderBy('competition_number');
    }

    /**
     * @return HasMany<CompetitionRound, $this>
     */
    public function rounds(): HasMany
    {
        return $this->hasMany(CompetitionRound::class)->orderBy('display_order')->orderBy('id');
    }
}
