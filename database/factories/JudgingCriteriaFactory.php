<?php

namespace Database\Factories;

use App\Models\JudgingCriteria;
use App\Models\JudgingRound;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JudgingCriteria>
 */
class JudgingCriteriaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judging_round_id' => JudgingRound::factory(),
            'name' => fake()->randomElement(['PBB', 'Variasi Formasi', 'Danton', 'Kostum']),
            'weight' => 25,
            'minimum_score' => 0,
            'maximum_score' => 100,
        ];
    }
}
