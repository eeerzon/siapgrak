<?php

namespace Database\Factories;

use App\Models\Judge;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Judge>
 */
class JudgeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->name(),
            'bio' => fake()->optional()->sentence(),
        ];
    }
}
