<?php

namespace App\Http\Controllers\Site;

use App\Enums\CommunityPostType;
use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function index(Request $request): View
    {
        $type = CommunityPostType::tryFrom($this->queryText($request, 'tipe') ?? '');
        $search = $this->queryText($request, 'q');

        $communityPosts = CommunityPost::published()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->search($search, ['title', 'excerpt', 'body'])
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('site.community.index', compact('communityPosts', 'type', 'search'));
    }

    public function show(string $slug): View
    {
        $communityPost = CommunityPost::published()->where('slug', $slug)->firstOrFail();

        return view('site.community.show', compact('communityPost'));
    }
}
