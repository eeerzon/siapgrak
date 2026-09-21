<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JudgingRound extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'weight',
        'status',
        'display_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'display_order' => 'integer',
            'status' => Status::class,
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
     * All criteria (root + subcriteria) — the view separates them by
     * parent_id. See JudgingCriteria::children()/parent().
     *
     * @return HasMany<JudgingCriteria, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(JudgingCriteria::class)->orderBy('display_order');
    }

    /**
     * @return HasMany<JudgeAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(JudgeAssignment::class);
    }

    /**
     * @return HasMany<ScoreSheet, $this>
     */
    public function scoreSheets(): HasMany
    {
        return $this->hasMany(ScoreSheet::class);
    }

    /**
     * Ordered by rank, then competition_number as a stable tiebreaker for
     * entries that share a rank (fr-result-calculator.md Keputusan D) — a
     * plain ->orderBy('rank') alone leaves ties in unspecified DB order.
     *
     * @return HasMany<CompetitionResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(CompetitionResult::class, 'judging_round_id')
            ->join('competition_entries', 'competition_entries.id', '=', 'competition_results.competition_entry_id')
            ->orderBy('competition_results.rank')
            ->orderBy('competition_entries.competition_number')
            ->select('competition_results.*');
    }
}
