<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Any authenticated user can see the list of organizations they belong to,
     * and can start creating a new one.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->membershipRole($user, $organization) !== null;
    }

    public function update(User $user, Organization $organization): bool
    {
        return in_array($this->membershipRole($user, $organization), [
            OrganizationRole::Owner,
            OrganizationRole::Admin,
        ], true);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->membershipRole($user, $organization) === OrganizationRole::Owner;
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return in_array($this->membershipRole($user, $organization), [
            OrganizationRole::Owner,
            OrganizationRole::Admin,
        ], true);
    }

    protected function membershipRole(User $user, Organization $organization): ?OrganizationRole
    {
        $membership = $organization->memberships()
            ->where('user_id', $user->id)
            ->where('status', Status::Active)
            ->first();

        return $membership?->role;
    }
}
