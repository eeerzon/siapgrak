<?php

namespace App\Http\Controllers\Dashboard\Content;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\Content\Concerns\StoresCoverImage;
use App\Http\Requests\Content\SaveAgendaItemRequest;
use App\Models\AgendaItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgendaItemController extends Controller
{
    use StoresCoverImage;

    public function index(Request $request): View
    {
        $search = $this->queryText($request, 'q');
        $agendaItems = AgendaItem::ownedBy($request->user())
            ->search($search, ['title', 'location'])
            ->latest('start_at')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.content.agenda.index', compact('agendaItems', 'search'));
    }

    public function create(): View
    {
        return view('dashboard.content.agenda.create', ['agendaItem' => new AgendaItem]);
    }

    public function store(SaveAgendaItemRequest $request): RedirectResponse
    {
        AgendaItem::create($this->withStoredCover($request, $request->validated()) + ['id_user' => $request->user()->id_user]);

        return redirect()
            ->route('dashboard.content.agenda.index')
            ->with('success', 'Agenda berhasil dibuat.');
    }

    public function edit(AgendaItem $agendaItem): View
    {
        $this->authorize('manage', $agendaItem);

        return view('dashboard.content.agenda.edit', compact('agendaItem'));
    }

    public function update(SaveAgendaItemRequest $request, AgendaItem $agendaItem): RedirectResponse
    {
        $this->authorize('manage', $agendaItem);

        $agendaItem->update($this->withStoredCover($request, $request->validated(), $agendaItem->cover_path));

        return redirect()
            ->route('dashboard.content.agenda.index')
            ->with('success', 'Agenda berhasil diperbarui.');
    }

    public function destroy(AgendaItem $agendaItem): RedirectResponse
    {
        $this->authorize('manage', $agendaItem);

        $agendaItem->delete();
        $this->deleteCover($agendaItem->cover_path);

        return redirect()
            ->route('dashboard.content.agenda.index')
            ->with('success', 'Agenda berhasil dihapus.');
    }
}
