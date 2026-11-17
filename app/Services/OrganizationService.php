<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Exceptions\CannotRemoveLastOwnerException;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    /**
     * Create an organization and make the creator its owner in a single transaction.
     * See fr-organization-tenancy.md RULE-1: no organization without an owner.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $creator): Organization
    {
        return DB::transaction(function () use ($data, $creator) {
            $organization = Organization::create($data);

            $organization->users()->attach($creator, [
                'role' => OrganizationRole::Owner,
                'status' => Status::Active,
            ]);

            return $organization;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Organization $organization, array $data): Organization
    {
        $organization->update($data);

        return $organization;
    }

    public function addMember(Organization $organization, User $user, OrganizationRole $role): OrganizationUser
    {
        $organization->users()->attach($user, [
            'role' => $role,
            'status' => Status::Active,
        ]);

        return $organization->memberships()->where('user_id', $user->id_user)->firstOrFail();
    }

    public function changeRole(OrganizationUser $membership, OrganizationRole $role): OrganizationUser
    {
        if ($membership->role === OrganizationRole::Owner
            && $role !== OrganizationRole::Owner
            && $this->ownerCount($membership->organization_id) <= 1) {
            throw new CannotRemoveLastOwnerException;
        }

        $membership->update(['role' => $role]);

        return $membership;
    }

    public function removeMember(OrganizationUser $membership): void
    {
        if ($membership->role === OrganizationRole::Owner
            && $this->ownerCount($membership->organization_id) <= 1) {
            throw new CannotRemoveLastOwnerException;
        }

        $membership->delete();
    }

    protected function ownerCount(int $organizationId): int
    {
        return OrganizationUser::query()
            ->where('organization_id', $organizationId)
            ->where('role', OrganizationRole::Owner)
            ->where('status', Status::Active)
            ->count();
    }
}
