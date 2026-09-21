<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Models\Organization;
use App\Models\Team;
use App\Services\TeamService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function __construct(protected TeamService $teams) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Team::class);

        $organizationIds = $this->teams->assignableOrganizationIds($request->user());

        $teams = Team::query()
            ->whereIn('organization_id', $organizationIds)
            ->with('organization')
            ->withCount('members')
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.teams.index', compact('teams'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Team::class);

        $organizations = Organization::whereIn('id', $this->teams->assignableOrganizationIds($request->user()))
            ->orderBy('name')
            ->get();

        return view('dashboard.teams.create', compact('organizations'));
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $team = $this->teams->create($request->validated());

        return redirect()
            ->route('dashboard.teams.show', $team)
            ->with('success', 'Team berhasil dibuat.');
    }

    public function show(Team $team): View
    {
        $this->authorize('view', $team);

        $team->load(['organization', 'members' => fn ($query) => $query->orderBy('name')]);

        return view('dashboard.teams.show', compact('team'));
    }

    public function edit(Team $team): View
    {
        $this->authorize('update', $team);

        return view('dashboard.teams.edit', compact('team'));
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $this->teams->update($team, $request->validated());

        return redirect()
            ->route('dashboard.teams.show', $team)
            ->with('success', 'Team berhasil diperbarui.');
    }
}
