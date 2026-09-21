<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'score_sheet_id',
        'judging_criteria_id',
        'score',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Never FLOAT (CLAUDE.md Aturan Kritis).
            'score' => 'decimal:3',
        ];
    }

    /**
     * @return BelongsTo<ScoreSheet, $this>
     */
    public function scoreSheet(): BelongsTo
    {
        return $this->belongsTo(ScoreSheet::class);
    }

    /**
     * @return BelongsTo<JudgingCriteria, $this>
     */
    public function criteria(): BelongsTo
    {
        return $this->belongsTo(JudgingCriteria::class, 'judging_criteria_id');
    }
}
