<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Shared by the admin-managed homepage content. The slug is generated once from
 * the title and stays stable afterwards, so published links never break.
 */
trait IsPublicContent
{
    protected static function bootIsPublicContent(): void
    {
        static::creating(function ($model): void {
            $model->slug = static::uniqueSlugFor($model->title);
        });

        static::saving(function ($model): void {
            if ($model->status === ContentStatus::Published && ! $model->published_at) {
                $model->published_at = now();
            }
        });
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ContentStatus::Published);
    }

    /**
     * Admin panel: only the content this user created (see PublicContentPolicy).
     *
     * @param  Builder<static>  $query
     */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where('id_user', $user->id_user);
    }

    /**
     * Case-insensitive "contains" match on any of the given columns. A blank term matches everything.
     *
     * @param  Builder<static>  $query
     * @param  list<string>  $columns
     */
    public function scopeSearch(Builder $query, ?string $term, array $columns): void
    {
        $query->when($term, fn (Builder $query, string $term) => $query->where(function (Builder $query) use ($term, $columns): void {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', "%{$term}%");
            }
        }));
    }

    private static function uniqueSlugFor(string $title): string
    {
        $base = Str::slug($title) ?: 'konten';
        $slug = $base;

        for ($suffix = 2; static::where('slug', $slug)->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
