<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\AgendaItem;
use App\Models\CommunityPost;
use App\Models\CompetitionListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Summary of the content (shown under "Events") owned by the logged-in user.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $competitions = CompetitionListing::ownedBy($user);

        $stats = [
            'published' => (clone $competitions)->published()->count(),
            'draft' => (clone $competitions)->where('status', ContentStatus::Draft)->count(),
            'registrationOpen' => (clone $competitions)->published()->upcoming()->registrationOpen()->count(),
            'upcomingAgenda' => AgendaItem::ownedBy($user)->upcoming()->count(),
            'communityPosts' => CommunityPost::ownedBy($user)->count(),
        ];

        $upcomingCompetitions = (clone $competitions)->upcoming()->orderBy('start_date')->limit(5)->get();
        $upcomingAgenda = AgendaItem::ownedBy($user)->upcoming()->orderBy('start_at')->limit(5)->get();

        return view('dashboard.content-overview', compact('stats', 'upcomingCompetitions', 'upcomingAgenda'));
    }

    /*
     * Old organization dashboard (view: dashboard.index), disabled 2026-11-10.
     * To restore: inject EventService in the constructor, import EventStatus,
     * ActivityLog, Event, Registration and Team, and call this from index().
     *
     * public function __construct(protected EventService $events) {}
     *
     * private function organizationOverview(Request $request): View
     * {
     *     $organizationIds = $this->events->assignableOrganizationIds($request->user());
     *
     *     $baseEvents = Event::whereIn('organization_id', $organizationIds);
     *
     *     $activeEventsCount = (clone $baseEvents)
     *         ->whereNotIn('status', [EventStatus::Draft, EventStatus::Cancelled, EventStatus::Finished])
     *         ->count();
     *
     *     $upcomingEvents = (clone $baseEvents)
     *         ->where('start_date', '>=', today())
     *         ->where('status', '!=', EventStatus::Cancelled);
     *
     *     $upcomingCount = (clone $upcomingEvents)->count();
     *
     *     $upcomingEvent = (clone $upcomingEvents)
     *         ->with('organization')
     *         ->withCount(['categories', 'registrations'])
     *         ->orderBy('start_date')
     *         ->first();
     *
     *     $teamsCount = Team::whereIn('organization_id', $organizationIds)->count();
     *
     *     $participantsCount = Registration::whereHas(
     *         'event',
     *         fn ($query) => $query->whereIn('organization_id', $organizationIds),
     *     )->count();
     *
     *     $recentActivity = ActivityLog::whereIn('organization_id', $organizationIds)
     *         ->with('user')
     *         ->latest()
     *         ->limit(8)
     *         ->get();
     *
     *     return view('dashboard.index', compact(
     *         'activeEventsCount', 'teamsCount', 'participantsCount', 'upcomingCount', 'upcomingEvent', 'recentActivity',
     *     ));
     * }
     */
}
