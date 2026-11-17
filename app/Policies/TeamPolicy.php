<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Team;
use App\Models\User;

/**
 * Team has no authorization pivot of its own — ownership is fully derived
 * from teams.organization_id via organization_users, same as OrganizationPolicy.
 * Do not use team_members for authorization (roster only).
 */
class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any active member of an organization can create a team under it
     * (validated against the submitted organization_id in TeamService, not
     * here — the policy only gates whether the form is reachable at all).
     */
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        return $this->membershipRole($user, $team) !== null;
    }

    public function update(User $user, Team $team): bool
    {
        return in_array($this->membershipRole($user, $team), [
            OrganizationRole::Owner,
            OrganizationRole::Admin,
        ], true);
    }

    public function manageMembers(User $user, Team $team): bool
    {
        return in_array($this->membershipRole($user, $team), [
            OrganizationRole::Owner,
            OrganizationRole::Admin,
        ], true);
    }

    protected function membershipRole(User $user, Team $team): ?OrganizationRole
    {
        if ($team->organization_id === null) {
            return null;
        }

        $membership = $team->organization->memberships()
            ->where('user_id', $user->id_user)
            ->where('status', Status::Active)
            ->first();

        return $membership?->role;
    }
}
