<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitionCriterion extends Model
{
    protected $table = 'competition_criteria';

    /**
     * @var list<string>
     */
    protected $fillable = ['competition_round_id', 'name', 'description', 'weight', 'minimum_score', 'maximum_score', 'display_order'];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'minimum_score' => 'decimal:3',
            'maximum_score' => 'decimal:3',
        ];
    }

    /**
     * @return BelongsTo<CompetitionRound, $this>
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(CompetitionRound::class, 'competition_round_id');
    }

    /**
     * @return HasMany<CompetitionScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(CompetitionScore::class);
    }
}
