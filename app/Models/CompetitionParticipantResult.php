<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Calculated ranking row; only CompetitionJudgingService writes these.
 */
class CompetitionParticipantResult extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'raw_score' => 'decimal:3',
            'weighted_score' => 'decimal:3',
            'rank' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<CompetitionParticipant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(CompetitionParticipant::class, 'competition_participant_id');
    }
}
