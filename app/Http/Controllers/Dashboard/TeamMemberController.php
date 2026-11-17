<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreTeamMemberRequest;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TeamMemberController extends Controller
{
    public function index(Team $team): View
    {
        $this->authorize('view', $team);

        $members = $team->members()->orderBy('name')->get();

        return view('dashboard.teams.members.index', compact('team', 'members'));
    }

    public function store(StoreTeamMemberRequest $request, Team $team): RedirectResponse
    {
        $team->members()->create($request->validated());

        return redirect()
            ->route('dashboard.teams.members.index', $team)
            ->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function destroy(Team $team, TeamMember $member): RedirectResponse
    {
        $this->authorize('manageMembers', $team);
        $this->assertMemberBelongsToTeam($team, $member);

        $member->delete();

        return redirect()
            ->route('dashboard.teams.members.index', $team)
            ->with('success', 'Anggota berhasil dihapus.');
    }

    protected function assertMemberBelongsToTeam(Team $team, TeamMember $member): void
    {
        abort_unless($member->team_id === $team->id, 404);
    }
}
