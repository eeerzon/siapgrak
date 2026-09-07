<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class TeamService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Team
    {
        return Team::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Team $team, array $data): Team
    {
        $team->update($data);

        return $team;
    }

    /**
     * Organization IDs the given user may assign a team to — active
     * memberships only. See fr-team-directory.md RULE-2: a user cannot
     * "park" a team under an organization they don't belong to, regardless
     * of what the request payload claims.
     *
     * @return Collection<int, int>
     */
    public function assignableOrganizationIds(User $user): Collection
    {
        return $user->organizations()
            ->wherePivot('status', Status::Active)
            ->pluck('organizations.id');
    }
}
