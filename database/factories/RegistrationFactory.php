<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Registration;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            // Keep the category on the same event as the registration.
            'event_category_id' => fn (array $attributes) => EventCategory::factory()->create([
                'event_id' => $attributes['event_id'],
            ])->id,
            'team_id' => Team::factory(),
            'submitted_by' => User::factory(),
            'status' => RegistrationStatus::Draft,
        ];
    }

    public function status(RegistrationStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
