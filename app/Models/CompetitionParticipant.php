<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitionParticipant extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['competition_listing_id', 'competition_category_id', 'name', 'institution', 'competition_number', 'performance_order'];

    protected function casts(): array
    {
        return ['performance_order' => 'integer'];
    }

    /**
     * @return BelongsTo<CompetitionCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    /**
     * @return HasMany<CompetitionJudging, $this>
     */
    public function judgings(): HasMany
    {
        return $this->hasMany(CompetitionJudging::class);
    }
}
