<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A judge for Info Lomba judging, owned by the admin who created it (id_user).
 * Logs in to the judge panel (/juri) with an access code, not a user account.
 */
class CompetitionJudge extends Model
{
    /** No 0/O or 1/I/L: codes are read aloud or handwritten on judges' table cards. */
    private const ACCESS_CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * access_code is set only through generateAccessCode(), never from a form.
     *
     * @var list<string>
     */
    protected $fillable = ['id_user', 'name', 'bio', 'status'];

    protected static function booted(): void
    {
        static::creating(function (CompetitionJudge $judge): void {
            $judge->access_code ??= self::generateAccessCode();
        });
    }

    public static function generateAccessCode(): string
    {
        do {
            $code = collect(range(1, 8))
                ->map(fn () => self::ACCESS_CODE_ALPHABET[random_int(0, strlen(self::ACCESS_CODE_ALPHABET) - 1)])
                ->implode('');
        } while (self::where('access_code', $code)->exists());

        return $code;
    }

    protected function casts(): array
    {
        return ['status' => Status::class];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where('id_user', $user->id_user);
    }

    /**
     * @return BelongsToMany<CompetitionRound, $this>
     */
    public function rounds(): BelongsToMany
    {
        return $this->belongsToMany(CompetitionRound::class, 'competition_judge_assignments')->withTimestamps();
    }
}
