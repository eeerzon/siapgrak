<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit record of a reopen action (fr-score-sheet-lifecycle.md
 * Keputusan H) — created once, never updated, requested_by may go null if
 * the user is later deleted but the row itself always survives.
 */
class ScoreSheetRevision extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'score_sheet_id',
        'requested_by',
        'reason',
        'previous_status',
    ];

    /**
     * @return BelongsTo<ScoreSheet, $this>
     */
    public function scoreSheet(): BelongsTo
    {
        return $this->belongsTo(ScoreSheet::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
