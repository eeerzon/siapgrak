<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Judge extends Model
{
    use HasFactory, HasPublicId;

    /**
     * Characters excluded on purpose: 0/O and 1/I/L — read aloud or
     * handwritten on a judge's table card on event day (fr-scoring.md
     * Keputusan A/B), ambiguity here is a real support problem.
     */
    protected const ACCESS_CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    protected const ACCESS_CODE_LENGTH = 8;

    /**
     * access_code is deliberately excluded — only JudgeController::
     * regenerateAccessCode may change it (direct property assignment), never
     * a generic mass-assigned form (fr-scoring.md Keputusan B).
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'user_id',
        'name',
        'photo_path',
        'bio',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (Judge $judge): void {
            $judge->access_code ??= self::generateAccessCode();
        });
    }

    public static function generateAccessCode(): string
    {
        do {
            $code = collect(range(1, self::ACCESS_CODE_LENGTH))
                ->map(fn () => self::ACCESS_CODE_ALPHABET[random_int(0, strlen(self::ACCESS_CODE_ALPHABET) - 1)])
                ->implode('');
        } while (self::where('access_code', $code)->exists());

        return $code;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }

    protected function publicIdPrefix(): string
    {
        return 'judge_';
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
