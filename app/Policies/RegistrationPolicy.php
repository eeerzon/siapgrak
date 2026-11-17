<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Registration;
use App\Models\User;

/**
 * Ownership is derived from registration.event.organization via
 * organization_users — same pattern as EventPolicy. event_staff.role is NOT
 * an authorization source yet (fr-registration.md Keputusan G).
 *
 * `create` and the nested-list gates live on EventPolicy::manageRegistrations
 * because they need the {event} context; RegistrationPolicy covers the
 * registration-level checks.
 */
class RegistrationPolicy
{
    public function view(User $user, Registration $registration): bool
    {
        return $this->membershipRole($user, $registration) !== null;
    }

    /**
     * Change status, and (via the isEditable guard in controllers) manage the
     * roster and documents.
     */
    public function updateStatus(User $user, Registration $registration): bool
    {
        return in_array($this->membershipRole($user, $registration), [
            OrganizationRole::Owner,
            OrganizationRole::Admin,
        ], true);
    }

    protected function membershipRole(User $user, Registration $registration): ?OrganizationRole
    {
        $membership = $registration->event->organization->memberships()
            ->where('user_id', $user->id_user)
            ->where('status', Status::Active)
            ->first();

        return $membership?->role;
    }
}
