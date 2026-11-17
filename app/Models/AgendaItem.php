<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\HasCoverImage;
use App\Models\Concerns\IsPublicContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AgendaItem extends Model
{
    use HasCoverImage, IsPublicContent;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'location',
        'cover_path',
        'start_at',
        'end_at',
        'status',
        'id_user',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'published_at' => 'datetime',
            'status' => ContentStatus::class,
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->whereRaw('COALESCE(end_at, start_at) >= ?', [now()->toDateTimeString()]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePast(Builder $query): void
    {
        $query->whereRaw('COALESCE(end_at, start_at) < ?', [now()->toDateTimeString()]);
    }
}
