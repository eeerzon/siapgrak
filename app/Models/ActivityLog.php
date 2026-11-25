<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Append-only audit trail, written exclusively via ActivityLogger
 * (fr-awards-finalization.md Keputusan E) — covers Sprint 12's own actions
 * only (finalize/publish/reopen/award changes), not a retrofit of earlier
 * sprints (that's Sprint 13 Polish).
 */
class ActivityLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'organization_id',
        'event_id',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
        'ip_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Short Indonesian label for the Dashboard (F2) recent-activity list.
     */
    public function label(): string
    {
        return match ($this->action) {
            'registration_approved' => 'Registrasi disetujui',
            'registration_rejected' => 'Registrasi ditolak',
            'score_sheet_submitted' => 'Lembar skor disubmit juri',
            'score_sheet_reopened' => 'Lembar skor dibuka kembali',
            'result_recalculated' => 'Hasil dihitung ulang',
            'result_finalized' => 'Hasil difinalisasi',
            'result_published' => 'Hasil dipublikasikan',
            'result_reopened' => 'Hasil dibuka kembali',
            'award_created' => 'Penghargaan ditambahkan',
            'award_updated' => 'Penghargaan diubah',
            'award_deleted' => 'Penghargaan dihapus',
            default => Str::headline($this->action),
        };
    }

    /**
     * user_id is NULL for judge-originated actions (judges aren't
     * Authenticatable, fr-scoring.md) — those carry identity in metadata
     * instead. Everything else is always done by an authenticated User.
     */
    public function actorLabel(): string
    {
        return $this->user?->name ?? 'Juri';
    }
}
