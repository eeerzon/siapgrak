<?php

namespace App\Http\Controllers\Dashboard\Content;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Content\Concerns\StoresCoverImage;
use App\Http\Requests\Content\SaveCommunityPostRequest;
use App\Models\CommunityPost;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommunityPostController extends Controller
{
    use StoresCoverImage;

    public function index(Request $request): View
    {
        $search = $this->queryText($request, 'q');
        $communityPosts = CommunityPost::ownedBy($request->user())
            ->search($search, ['title', 'excerpt'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.content.community.index', compact('communityPosts', 'search'));
    }

    public function create(): View
    {
        return view('dashboard.content.community.create', ['communityPost' => new CommunityPost]);
    }

    public function store(SaveCommunityPostRequest $request): RedirectResponse
    {
        CommunityPost::create($this->withStoredCover($request, $request->validated()) + [
            'id_user' => $request->user()->id_user,
        ]);

        return redirect()
            ->route('dashboard.content.community.index')
            ->with('success', 'Kabar berhasil dibuat.');
    }

    public function edit(CommunityPost $communityPost): View
    {
        $this->authorize('manage', $communityPost);

        return view('dashboard.content.community.edit', compact('communityPost'));
    }

    public function update(SaveCommunityPostRequest $request, CommunityPost $communityPost): RedirectResponse
    {
        $this->authorize('manage', $communityPost);

        $communityPost->update($this->withStoredCover($request, $request->validated(), $communityPost->cover_path));

        return redirect()
            ->route('dashboard.content.community.index')
            ->with('success', 'Kabar berhasil diperbarui.');
    }

    public function destroy(CommunityPost $communityPost): RedirectResponse
    {
        $this->authorize('manage', $communityPost);

        $communityPost->delete();
        $this->deleteCover($communityPost->cover_path);

        return redirect()
            ->route('dashboard.content.community.index')
            ->with('success', 'Kabar berhasil dihapus.');
    }
}
