<?php

namespace App\Models;

use App\Enums\CommunityPostType;
use App\Enums\ContentStatus;
use App\Models\Concerns\HasCoverImage;
use App\Models\Concerns\IsPublicContent;
use Illuminate\Database\Eloquent\Model;

class CommunityPost extends Model
{
    use HasCoverImage, IsPublicContent;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'title',
        'excerpt',
        'body',
        'cover_path',
        'status',
        'id_user',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CommunityPostType::class,
            'published_at' => 'datetime',
            'status' => ContentStatus::class,
        ];
    }
}
