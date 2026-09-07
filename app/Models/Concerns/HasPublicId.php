<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasPublicId
{
    protected static function bootHasPublicId(): void
    {
        static::creating(function ($model): void {
            if (empty($model->public_id)) {
                $model->public_id = $model->publicIdPrefix().strtolower((string) Str::ulid());
            }
        });
    }

    /**
     * Short prefix identifying the resource type in its public_id, e.g. "org_", "usr_".
     */
    abstract protected function publicIdPrefix(): string;
}
