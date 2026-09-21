<?php

namespace App\Models;

use App\Enums\ResultStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pure derived data — always recalculable from scores, rebuilt wholesale by
 * ResultService::calculateForRound() on every "Hitung Ulang"
 * (fr-result-calculator.md Keputusan F). Nothing here is mass-fillable —
 * every field, including penalty_score, is set via direct property
 * assignment in ResultService/ResultCalculator only (Keputusan G — same
 * defense-in-depth pattern as CompetitionEntry.status, ScoreSheet.status).
 */
class CompetitionResult extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [];

    /**
     * Mirrors the DB column default — see ScoreSheet's identical note
     * (fr-score-sheet-lifecycle.md) for why this matters: without it, a
     * freshly-built-but-unsaved instance has 'status' genuinely null in PHP.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'penalty_score' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_score' => 'decimal:3',
            'weighted_score' => 'decimal:3',
            'penalty_score' => 'decimal:3',
            'final_score' => 'decimal:3',
            'rank' => 'integer',
            'status' => ResultStatus::class,
            'finalized_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EventCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    /**
     * @return BelongsTo<JudgingRound, $this>
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(JudgingRound::class, 'judging_round_id');
    }

    /**
     * @return BelongsTo<CompetitionEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(CompetitionEntry::class, 'competition_entry_id');
    }
}
