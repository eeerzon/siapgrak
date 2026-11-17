<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitionScore extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['competition_judging_id', 'competition_criterion_id', 'score', 'notes'];

    protected function casts(): array
    {
        return ['score' => 'decimal:3'];
    }

    /**
     * @return BelongsTo<CompetitionCriterion, $this>
     */
    public function criterion(): BelongsTo
    {
        return $this->belongsTo(CompetitionCriterion::class, 'competition_criterion_id');
    }
}
