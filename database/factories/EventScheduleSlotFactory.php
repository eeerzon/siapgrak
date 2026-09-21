<?php

namespace Database\Factories;

use App\Enums\ScheduleSlotType;
use App\Models\Event;
use App\Models\EventScheduleSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventScheduleSlot>
 */
class EventScheduleSlotFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week 08:00', '+1 week 16:00');

        return [
            'event_id' => Event::factory(),
            'title' => fake()->randomElement(['Opening Ceremony', 'Technical Meeting', 'Break', null]),
            'start_at' => $start,
            'end_at' => (clone $start)->modify('+30 minutes'),
            'venue_area' => fake()->optional()->randomElement(['Panggung Utama', 'Lapangan A']),
            'slot_type' => fake()->randomElement(ScheduleSlotType::cases()),
            'display_order' => 0,
        ];
    }
}
