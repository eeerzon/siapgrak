<?php

namespace Database\Factories;

use App\Models\Judge;
use App\Models\JudgeAssignment;
use App\Models\JudgingRound;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JudgeAssignment>
 */
class JudgeAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judging_round_id' => JudgingRound::factory(),
            'judge_id' => Judge::factory(),
            'judging_criteria_id' => null,
        ];
    }
}
