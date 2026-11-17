<?php

namespace App\Http\Controllers\Dashboard\Content;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Content\Concerns\StoresCoverImage;
use App\Http\Requests\Content\SaveCompetitionListingRequest;
use App\Models\CompetitionListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompetitionListingController extends Controller
{
    use StoresCoverImage;

    public function index(Request $request): View
    {
        $search = $this->queryText($request, 'q');
        $competitions = CompetitionListing::ownedBy($request->user())
            ->search($search, ['title', 'organizer_name', 'location'])
            ->latest('start_date')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.content.competitions.index', compact('competitions', 'search'));
    }

    public function create(): View
    {
        return view('dashboard.content.competitions.create', ['competition' => new CompetitionListing]);
    }

    public function store(SaveCompetitionListingRequest $request): RedirectResponse
    {
        CompetitionListing::create($this->withStoredCover($request, $request->payload()) + [
            'id_user' => $request->user()->id_user,
        ]);

        return redirect()
            ->route('dashboard.content.competitions.index')
            ->with('success', 'Info lomba berhasil dibuat.');
    }

    public function show(CompetitionListing $competition): View
    {
        $this->authorize('manage', $competition);

        return view('dashboard.content.competitions.show', compact('competition'));
    }

    public function edit(CompetitionListing $competition): View
    {
        $this->authorize('manage', $competition);

        return view('dashboard.content.competitions.edit', compact('competition'));
    }

    public function update(SaveCompetitionListingRequest $request, CompetitionListing $competition): RedirectResponse
    {
        $this->authorize('manage', $competition);

        $competition->update($this->withStoredCover($request, $request->payload(), $competition->cover_path));

        return redirect()
            ->route('dashboard.content.competitions.index')
            ->with('success', 'Info lomba berhasil diperbarui.');
    }

    public function destroy(CompetitionListing $competition): RedirectResponse
    {
        $this->authorize('manage', $competition);

        // Judging history must never disappear with the listing (CLAUDE.md constitution).
        if ($competition->categories()->exists()) {
            return back()->with('error', 'Info lomba yang sudah punya data penjurian tidak bisa dihapus. Ubah statusnya ke draft kalau tidak ingin tampil.');
        }

        $competition->delete();
        $this->deleteCover($competition->cover_path);

        return redirect()
            ->route('dashboard.content.competitions.index')
            ->with('success', 'Info lomba berhasil dihapus.');
    }
}
