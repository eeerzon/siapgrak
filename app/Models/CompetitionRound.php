<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitionRound extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['competition_category_id', 'name', 'display_order'];

    protected function casts(): array
    {
        return ['results_published_at' => 'datetime'];
    }

    public function isPublished(): bool
    {
        return $this->results_published_at !== null;
    }

    /**
     * @return BelongsTo<CompetitionCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CompetitionCategory::class, 'competition_category_id');
    }

    /**
     * @return HasMany<CompetitionCriterion, $this>
     */
    public function criteria(): HasMany
    {
        return $this->hasMany(CompetitionCriterion::class)->orderBy('display_order')->orderBy('id');
    }

    /**
     * @return BelongsToMany<CompetitionJudge, $this>
     */
    public function judges(): BelongsToMany
    {
        return $this->belongsToMany(CompetitionJudge::class, 'competition_judge_assignments')->withTimestamps()->orderBy('name');
    }

    /**
     * Teams chosen for this round. Only used for rounds after the first: the first round of a
     * category is open to every team in it. Use CompetitionJudgingService::entrants() to get
     * the teams of any round.
     *
     * @return BelongsToMany<CompetitionParticipant, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(CompetitionParticipant::class, 'competition_round_participants')
            ->withTimestamps()
            ->orderBy('performance_order')
            ->orderBy('competition_number');
    }

    /**
     * @return HasMany<CompetitionJudging, $this>
     */
    public function judgings(): HasMany
    {
        return $this->hasMany(CompetitionJudging::class);
    }

    /**
     * @return HasMany<CompetitionParticipantResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(CompetitionParticipantResult::class)->orderBy('rank');
    }
}
