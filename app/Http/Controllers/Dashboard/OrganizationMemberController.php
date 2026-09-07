<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\OrganizationRole;
use App\Exceptions\CannotRemoveLastOwnerException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\AddOrganizationMemberRequest;
use App\Http\Requests\Organization\UpdateOrganizationMemberRequest;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class OrganizationMemberController extends Controller
{
    public function __construct(protected OrganizationService $organizations) {}

    public function index(Organization $organization): View
    {
        $this->authorize('manageMembers', $organization);

        $memberships = $organization->memberships()
            ->with('user')
            ->orderBy('created_at')
            ->get();

        return view('dashboard.organizations.members.index', compact('organization', 'memberships'));
    }

    public function store(AddOrganizationMemberRequest $request, Organization $organization): RedirectResponse
    {
        $user = User::where('email', $request->validated('email'))->firstOrFail();

        if ($organization->memberships()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['email' => 'User ini sudah jadi anggota organisasi.']);
        }

        $this->organizations->addMember($organization, $user, $request->enum('role', OrganizationRole::class));

        return redirect()
            ->route('dashboard.organizations.members.index', $organization)
            ->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function update(UpdateOrganizationMemberRequest $request, Organization $organization, OrganizationUser $membership): RedirectResponse
    {
        $this->assertMembershipBelongsToOrganization($organization, $membership);

        try {
            $this->organizations->changeRole($membership, $request->enum('role', OrganizationRole::class));
        } catch (CannotRemoveLastOwnerException $e) {
            return back()->withErrors(['role' => $e->getMessage()]);
        }

        return redirect()
            ->route('dashboard.organizations.members.index', $organization)
            ->with('success', 'Role anggota berhasil diubah.');
    }

    public function destroy(Organization $organization, OrganizationUser $membership): RedirectResponse
    {
        $this->authorize('manageMembers', $organization);
        $this->assertMembershipBelongsToOrganization($organization, $membership);

        try {
            $this->organizations->removeMember($membership);
        } catch (CannotRemoveLastOwnerException $e) {
            return back()->withErrors(['member' => $e->getMessage()]);
        }

        return redirect()
            ->route('dashboard.organizations.members.index', $organization)
            ->with('success', 'Anggota berhasil dihapus.');
    }

    /**
     * Defense against URL manipulation: membership {membership} must actually
     * belong to organization {organization} from the route, not just exist.
     */
    protected function assertMembershipBelongsToOrganization(Organization $organization, OrganizationUser $membership): void
    {
        abort_unless($membership->organization_id === $organization->id, 404);
    }
}
