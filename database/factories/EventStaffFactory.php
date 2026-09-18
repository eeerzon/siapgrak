<?php

namespace Database\Factories;

use App\Enums\EventStaffRole;
use App\Models\Event;
use App\Models\EventStaff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventStaff>
 */
class EventStaffFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'role' => fake()->randomElement(EventStaffRole::cases()),
        ];
    }
}
