<?php

namespace Database\Factories;

use App\Models\JudgingCriteria;
use App\Models\Score;
use App\Models\ScoreSheet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Score>
 */
class ScoreFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'score_sheet_id' => ScoreSheet::factory(),
            'judging_criteria_id' => JudgingCriteria::factory(),
            'score' => fake()->randomFloat(3, 0, 100),
        ];
    }
}
