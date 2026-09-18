<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'LKBB '.fake()->unique()->words(2, true);
        $start = fake()->dateTimeBetween('+2 weeks', '+3 months');

        return [
            'organization_id' => Organization::factory(),
            'created_by' => User::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'short_description' => fake()->optional()->sentence(),
            'description' => fake()->optional()->paragraph(),
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+1 day'),
            'venue_name' => fake()->optional()->company(),
            'city_code' => fake()->optional()->city(),
            'province_code' => fake()->optional()->stateAbbr(),
            'status' => EventStatus::Draft,
        ];
    }

    public function status(EventStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
