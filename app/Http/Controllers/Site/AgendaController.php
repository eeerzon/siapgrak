<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\AgendaItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function index(Request $request): View
    {
        $showPast = $request->boolean('arsip');
        $search = $this->queryText($request, 'q');

        $agendaItems = AgendaItem::published()
            ->search($search, ['title', 'location', 'description'])
            ->when($showPast,
                fn ($query) => $query->past()->orderByDesc('start_at'),
                fn ($query) => $query->upcoming()->orderBy('start_at'))
            ->paginate(8)
            ->withQueryString();

        return view('site.agenda.index', compact('agendaItems', 'showPast', 'search'));
    }

    public function show(string $slug): View
    {
        $agendaItem = AgendaItem::published()->where('slug', $slug)->firstOrFail();

        return view('site.agenda.show', compact('agendaItem'));
    }
}
