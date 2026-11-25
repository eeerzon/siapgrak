<?php

namespace App\Http\Controllers\Site;

use App\Enums\CompetitionLevel;
use App\Http\Controllers\Controller;
use App\Models\CompetitionListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompetitionController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $showPast = $request->boolean('arsip');

        $filters = [
            'q' => $this->queryText($request, 'q'),
            'level' => CompetitionLevel::tryFrom($this->queryText($request, 'jenjang') ?? ''),
            'province' => in_array($request->query('provinsi'), config('siapgrak.provinces'), true) ? $request->query('provinsi') : null,
            'open' => ! $showPast && $request->boolean('buka'),
        ];

        $competitions = CompetitionListing::published()
            ->filter($filters)
            ->when($showPast,
                fn ($query) => $query->past()->orderByDesc('start_date'),
                fn ($query) => $query->upcoming()->orderBy('start_date'))
            ->paginate(8)
            ->withQueryString();

        if ($redirect = $this->redirectPastLastPage($competitions)) {
            return $redirect;
        }

        // Only provinces that have a competition on this tab, so picking one never gives an empty list.
        // The chosen one stays in the list even when it has none left (e.g. after switching tabs).
        $provinces = CompetitionListing::published()
            ->when($showPast, fn ($query) => $query->past(), fn ($query) => $query->upcoming())
            ->whereIn('province', config('siapgrak.provinces'))
            ->distinct()
            ->pluck('province')
            ->push($filters['province'])
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $isFiltered = collect($filters)->filter()->isNotEmpty();

        return view('site.competitions.index', compact('competitions', 'showPast', 'filters', 'provinces', 'isFiltered'));
    }

    public function show(string $slug): View
    {
        $competition = CompetitionListing::published()->where('slug', $slug)->firstOrFail();

        return view('site.competitions.show', compact('competition'));
    }
}
