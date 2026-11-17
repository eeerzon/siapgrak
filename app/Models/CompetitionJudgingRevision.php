<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit row written whenever an admin reopens a sent or locked judging.
 */
class CompetitionJudgingRevision extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = ['competition_judging_id', 'id_user', 'previous_status', 'reason'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
