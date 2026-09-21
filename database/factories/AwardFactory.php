<?php

namespace Database\Factories;

use App\Models\Award;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Award>
 */
class AwardFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->randomElement(['Juara 1', 'Juara 2', 'Juara 3', 'Best Danton', 'Best Costume']),
            'award_type' => 'ranking',
        ];
    }
}
