<?php

namespace App\Services;

use App\Enums\Status;
use App\Models\Judge;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Mirrors TeamService — judges are org-scoped reusable master data
 * (fr-judging-setup.md Keputusan A).
 */
class JudgeService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Judge
    {
        return Judge::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Judge $judge, array $data): Judge
    {
        $judge->update($data);

        return $judge;
    }

    /**
     * @return Collection<int, int>
     */
    public function assignableOrganizationIds(User $user): Collection
    {
        return $user->organizations()
            ->wherePivot('status', Status::Active)
            ->pluck('organizations.id');
    }
}
