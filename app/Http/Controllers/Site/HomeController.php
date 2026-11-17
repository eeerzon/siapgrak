<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\AgendaItem;
use App\Models\CommunityPost;
use App\Models\CompetitionListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class HomeController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('dashboard.index');
        }

        return view('site.home', [
            'competitions' => CompetitionListing::published()->upcoming()->orderBy('start_date')->limit(4)->get(),
            'agendaItems' => AgendaItem::published()->upcoming()->orderBy('start_at')->limit(4)->get(),
            'communityPosts' => CommunityPost::published()->latest('published_at')->limit(3)->get(),
            'openCompetitionsCount' => CompetitionListing::published()->upcoming()->registrationOpen()->count(),
            'upcomingAgendaCount' => AgendaItem::published()->upcoming()->count(),
        ]);
    }
}
