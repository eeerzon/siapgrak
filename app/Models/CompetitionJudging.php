<?php

namespace App\Models;

use App\Enums\ScoreSheetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * "Penjurian": one judge's score sheet for one participant in one round.
 * Status and timestamps are never mass-assigned; CompetitionJudgingService moves them.
 */
class CompetitionJudging extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['competition_listing_id', 'competition_round_id', 'competition_participant_id', 'competition_judge_id'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'draft', 'revision_count' => 0];

    protected function casts(): array
    {
        return [
            'status' => ScoreSheetStatus::class,
            'submitted_at' => 'datetime',
            'locked_at' => 'datetime',
            'revision_count' => 'integer',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === ScoreSheetStatus::Draft;
    }

    /**
     * @return BelongsTo<CompetitionRound, $this>
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(CompetitionRound::class, 'competition_round_id');
    }

    /**
     * @return BelongsTo<CompetitionParticipant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(CompetitionParticipant::class, 'competition_participant_id');
    }

    /**
     * @return BelongsTo<CompetitionJudge, $this>
     */
    public function judge(): BelongsTo
    {
        return $this->belongsTo(CompetitionJudge::class, 'competition_judge_id');
    }

    /**
     * @return HasMany<CompetitionScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(CompetitionScore::class);
    }

    /**
     * @return HasMany<CompetitionJudgingRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(CompetitionJudgingRevision::class)->latest();
    }
}
