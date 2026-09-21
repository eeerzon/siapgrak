<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * judging_criteria_id NULL means the judge scores the whole round
 * (fr-judge-assignment.md). No update endpoint — change scope by deleting
 * and re-assigning (Keputusan F).
 */
class JudgeAssignment extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'judge_id',
        'judging_criteria_id',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => Status::class,
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
     * @return BelongsTo<Judge, $this>
     */
    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    /**
     * @return BelongsTo<JudgingCriteria, $this>
     */
    public function criteria(): BelongsTo
    {
        return $this->belongsTo(JudgingCriteria::class, 'judging_criteria_id');
    }
}
