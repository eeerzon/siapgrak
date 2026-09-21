<?php

namespace App\Services;

use App\Models\EventCategory;
use App\Models\JudgeAssignment;
use App\Models\JudgingCriteria;
use App\Models\JudgingRound;

/**
 * The max-nesting-depth-2 rule (fr-judging-setup.md Keputusan D) is enforced
 * entirely by the Form Request's validation ("parent must belong to this
 * round AND be a root criterion" — Rule::exists()->whereNull('parent_id')),
 * so there is no separate depth check to duplicate here.
 */
class JudgingService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createRound(EventCategory $category, array $data): JudgingRound
    {
        return $category->judgingRounds()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRound(JudgingRound $round, array $data): JudgingRound
    {
        $round->update($data);

        return $round;
    }

    public function deleteRound(JudgingRound $round): void
    {
        $round->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCriteria(JudgingRound $round, array $data): JudgingCriteria
    {
        return $round->criteria()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCriteria(JudgingCriteria $criteria, array $data): JudgingCriteria
    {
        $criteria->update($data);

        return $criteria;
    }

    public function deleteCriteria(JudgingCriteria $criteria): void
    {
        $criteria->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAssignment(JudgingRound $round, array $data): JudgeAssignment
    {
        return $round->assignments()->create($data);
    }

    public function deleteAssignment(JudgeAssignment $assignment): void
    {
        $assignment->delete();
    }
}
