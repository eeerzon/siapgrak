<?php

namespace App\Models;

use App\Enums\CompetitionLevel;
use App\Enums\ContentStatus;
use App\Models\Concerns\HasCoverImage;
use App\Models\Concerns\IsPublicContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitionListing extends Model
{
    use HasCoverImage, IsPublicContent;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'excerpt',
        'body',
        'cover_path',
        'organizer_name',
        'location',
        'province',
        'start_date',
        'end_date',
        'levels',
        'registration_deadline',
        'technical_meeting_date',
        'technical_meeting_location',
        'is_managed',
        'kreen_slug',
        'external_url',
        'contact_persons',
        'status',
        'id_user',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_deadline' => 'date',
            'technical_meeting_date' => 'date',
            'levels' => 'array',
            'contact_persons' => 'array',
            'is_managed' => 'boolean',
            'published_at' => 'datetime',
            'status' => ContentStatus::class,
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->whereRaw('COALESCE(end_date, start_date) >= ?', [today()->toDateString()]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePast(Builder $query): void
    {
        $query->whereRaw('COALESCE(end_date, start_date) < ?', [today()->toDateString()]);
    }

    /**
     * Still accepting registrations: before the deadline, or, without a
     * deadline, until the competition starts.
     *
     * @param  Builder<self>  $query
     */
    public function scopeRegistrationOpen(Builder $query): void
    {
        $today = today()->toDateString();

        $query->where(fn (Builder $query) => $query
            ->where('registration_deadline', '>=', $today)
            ->orWhere(fn (Builder $query) => $query
                ->whereNull('registration_deadline')
                ->where('start_date', '>=', $today)));
    }

    /**
     * Public filter. Expects values already validated by the controller.
     *
     * @param  Builder<self>  $query
     * @param  array{q?: ?string, level?: ?CompetitionLevel, province?: ?string, open?: bool}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->search($filters['q'] ?? null, ['title', 'organizer_name', 'location'])
            ->when($filters['level'] ?? null, fn (Builder $query, CompetitionLevel $level) => $query->whereJsonContains('levels', $level->value))
            ->when($filters['province'] ?? null, fn (Builder $query, string $province) => $query->where('province', $province))
            ->when($filters['open'] ?? false, fn (Builder $query) => $query->registrationOpen());
    }

    /**
     * @return list<string>
     */
    public function levelLabels(): array
    {
        return collect($this->levels ?? [])
            ->map(fn (string $level) => CompetitionLevel::tryFrom($level)?->label())
            ->filter()
            ->values()
            ->all();
    }

    public function isRegistrationOpen(): bool
    {
        return ($this->registration_deadline ?? $this->start_date)->gte(today());
    }

    /**
     * Registration goes through Kreenconnect or the organizer's link; when both
     * are missing, people register by contacting the contact persons.
     */
    public function registrationUrl(): ?string
    {
        return $this->kreenUrl() ?? ($this->is_managed ? null : $this->external_url);
    }

    /**
     * Indonesian numbers like 0812-xxx become 62812xxx for wa.me.
     */
    public static function whatsappUrl(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        return 'https://wa.me/'.(str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits);
    }

    /**
     * Null until KREENCONNECT_EVENT_BASE_URL is configured, so the page never
     * renders a broken transaction link.
     */
    /**
     * Judging setup lives on the listing itself (separate from the organizer event module).
     *
     * @return HasMany<CompetitionCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(CompetitionCategory::class)->orderBy('display_order')->orderBy('id');
    }

    /**
     * @return HasMany<CompetitionParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(CompetitionParticipant::class);
    }

    /**
     * @return HasMany<CompetitionJudging, $this>
     */
    public function judgings(): HasMany
    {
        return $this->hasMany(CompetitionJudging::class);
    }

    public function kreenUrl(): ?string
    {
        $baseUrl = config('siapgrak.kreenconnect_event_base_url');

        if (! $this->is_managed || ! $this->kreen_slug || ! $baseUrl) {
            return null;
        }

        return rtrim($baseUrl, '/').'/'.rawurlencode($this->kreen_slug);
    }
}
