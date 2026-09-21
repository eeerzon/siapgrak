<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Max nesting depth is 2 (root + subcriteria) — enforced in JudgingService,
 * not here (fr-judging-setup.md Keputusan D).
 */
class JudgingCriteria extends Model
{
    use HasFactory;

    protected $table = 'judging_criteria';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'parent_id',
        'name',
        'description',
        'weight',
        'minimum_score',
        'maximum_score',
        'display_order',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'minimum_score' => 'decimal:3',
            'maximum_score' => 'decimal:3',
            'display_order' => 'integer',
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
     * @return BelongsTo<JudgingCriteria, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<JudgingCriteria, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('display_order');
    }

    /**
     * @return HasMany<Score, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(Score::class, 'judging_criteria_id');
    }

    /**
     * Only leaf criteria are directly scored — a criteria with subcriteria is
     * pure grouping, its score is rolled up from children instead
     * (fr-scoring.md Keputusan C).
     */
    public function isLeaf(): bool
    {
        return $this->children->isEmpty();
    }
}
