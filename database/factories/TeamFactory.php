<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Paskibra '.fake()->unique()->company();

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'short_name' => fake()->optional()->lexify('???'),
            'province_code' => fake()->optional()->stateAbbr(),
            'city_code' => fake()->optional()->city(),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
