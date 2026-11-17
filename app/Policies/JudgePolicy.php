<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Judge;
use App\Models\User;

/**
 * Ownership derived from judges.organization_id via organization_users — same
 * pattern as TeamPolicy (fr-judging-setup.md Keputusan A: organization_id was
 * added to close a tenant-isolation gap in the original blueprint).
 */
class JudgePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user can reach the create form; the submitted
     * organization_id is validated against active memberships in the Form
     * Request (pola StoreTeamRequest).
     */
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Judge $judge): bool
    {
        return $this->membershipRole($user, $judge) !== null;
    }

    public function update(User $user, Judge $judge): bool
    {
        return in_array($this->membershipRole($user, $judge), [
            OrganizationRole::Owner,
            OrganizationRole::Admin,
        ], true);
    }

    protected function membershipRole(User $user, Judge $judge): ?OrganizationRole
    {
        if ($judge->organization_id === null) {
            return null;
        }

        $membership = $judge->organization->memberships()
            ->where('user_id', $user->id_user)
            ->where('status', Status::Active)
            ->first();

        return $membership?->role;
    }
}
