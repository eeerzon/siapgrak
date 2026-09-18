<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventCategory>
 */
class EventCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $min = fake()->numberBetween(10, 14);

        return [
            'event_id' => Event::factory(),
            'name' => fake()->unique()->randomElement(['SMA Putra', 'SMA Putri', 'SMP Putra', 'SMP Putri', 'Madya', 'Utama']),
            // 4 digits, not 2 — tests that create several categories for the
            // same event (unique per event_id+code) hit real birthday-paradox
            // collisions at a 2-digit (100-slot) pool (observed intermittent
            // failures in QueryCountTest/CompetitionNumberTest).
            'code' => fake()->optional()->bothify('CAT-####'),
            'description' => fake()->optional()->sentence(),
            'quota' => fake()->optional()->numberBetween(8, 40),
            'min_members' => $min,
            'max_members' => $min + fake()->numberBetween(1, 6),
            'display_order' => fake()->numberBetween(0, 10),
        ];
    }
}
