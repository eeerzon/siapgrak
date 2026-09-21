<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Team;
use App\Services\EventService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * F2 — readiness summary, not an analytics page (frontend-plan.md §Dashboard).
 * Everything scoped to the user's active organization memberships, same
 * helper EventController/TeamController already use for their own indexes.
 */
class DashboardController extends Controller
{
    public function __construct(protected EventService $events) {}

    public function index(Request $request): View
    {
        $organizationIds = $this->events->assignableOrganizationIds($request->user());

        $baseEvents = Event::whereIn('organization_id', $organizationIds);

        $activeEventsCount = (clone $baseEvents)
            ->whereNotIn('status', [EventStatus::Draft, EventStatus::Cancelled, EventStatus::Finished])
            ->count();

        $upcomingEvents = (clone $baseEvents)
            ->where('start_date', '>=', today())
            ->where('status', '!=', EventStatus::Cancelled);

        $upcomingCount = (clone $upcomingEvents)->count();

        $upcomingEvent = (clone $upcomingEvents)
            ->with('organization')
            ->withCount(['categories', 'registrations'])
            ->orderBy('start_date')
            ->first();

        $teamsCount = Team::whereIn('organization_id', $organizationIds)->count();

        $participantsCount = Registration::whereHas(
            'event',
            fn ($query) => $query->whereIn('organization_id', $organizationIds),
        )->count();

        $recentActivity = ActivityLog::whereIn('organization_id', $organizationIds)
            ->with('user')
            ->latest()
            ->limit(8)
            ->get();

        return view('dashboard.index', compact(
            'activeEventsCount', 'teamsCount', 'participantsCount', 'upcomingCount', 'upcomingEvent', 'recentActivity',
        ));
    }
}
