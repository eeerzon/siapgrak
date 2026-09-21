<?php

namespace Database\Factories;

use App\Models\EventCategory;
use App\Models\JudgingRound;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JudgingRound>
 */
class JudgingRoundFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_category_id' => EventCategory::factory(),
            'name' => fake()->randomElement(['Final', 'Preliminary']),
            'weight' => 100,
        ];
    }
}
