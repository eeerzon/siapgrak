<?php

namespace App\Models;

use App\Enums\ScoreSheetStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lazily created on the judge's first save (fr-scoring.md Keputusan F).
 * status/submitted_at/locked_at/revision_count are lifecycle-controlled and
 * deliberately NOT mass-fillable — only ScoreSheetService::submit/lock/reopen
 * (fr-score-sheet-lifecycle.md) may change them, via direct property
 * assignment (same pattern as CompetitionEntry.status/competition_number).
 */
class ScoreSheet extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'judging_round_id',
        'competition_entry_id',
        'judge_id',
    ];

    /**
     * Mirrors the DB column default. Without this, a freshly created (not
     * re-fetched) instance — e.g. straight out of firstOrCreate() in
     * ScoreSheetService::getOrCreateScoreSheet() — has 'status' genuinely
     * unset in PHP even though the row's DB default applied, since Eloquent
     * never re-reads server-side defaults after INSERT. $sheet->status would
     * silently resolve to null instead of Draft.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ScoreSheetStatus::class,
            'submitted_at' => 'datetime',
            'locked_at' => 'datetime',
            'revision_count' => 'integer',
        ];
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

    /**
     * @return BelongsTo<Judge, $this>
     */
    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    /**
     * @return HasMany<Score, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    /**
     * @return HasMany<ScoreSheetRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(ScoreSheetRevision::class)->latest('id');
    }
}
