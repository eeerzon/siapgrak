<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registration\StoreRegistrationMemberRequest;
use App\Models\Registration;
use App\Models\RegistrationMember;
use Illuminate\Http\RedirectResponse;

/**
 * Roster is a SNAPSHOT (fr-registration.md Keputusan D): we copy name/role/
 * gender into registration_members; team_member_id is provenance only.
 * Mutations allowed only while the registration is editable (RULE-6).
 */
class RegistrationMemberController extends Controller
{
    public function store(StoreRegistrationMemberRequest $request, Registration $registration): RedirectResponse
    {
        abort_unless($registration->isEditable(), 403);

        $registration->members()->create($request->validated());

        return $this->back($registration, 'Anggota roster ditambahkan.');
    }

    public function destroy(Registration $registration, RegistrationMember $member): RedirectResponse
    {
        $this->authorize('updateStatus', $registration);
        $this->assertBelongsToRegistration($registration, $member);
        abort_unless($registration->isEditable(), 403);

        $member->delete();

        return $this->back($registration, 'Anggota roster dihapus.');
    }

    protected function assertBelongsToRegistration(Registration $registration, RegistrationMember $member): void
    {
        abort_unless($member->registration_id === $registration->id, 404);
    }

    protected function back(Registration $registration, string $message): RedirectResponse
    {
        return redirect()
            ->route('dashboard.events.registrations.show', [$registration->event_id, $registration])
            ->with('success', $message);
    }
}
