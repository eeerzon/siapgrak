<?php

namespace Database\Factories;

use App\Enums\CompetitionEntryStatus;
use App\Models\CompetitionEntry;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Registration;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionEntry>
 */
class CompetitionEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            // Keep category/team/registration consistent with the event, same
            // approach as RegistrationFactory.
            'event_category_id' => fn (array $attributes) => EventCategory::factory()->create([
                'event_id' => $attributes['event_id'],
            ])->id,
            'team_id' => Team::factory(),
            'registration_id' => fn (array $attributes) => Registration::factory()->create([
                'event_id' => $attributes['event_id'],
                'event_category_id' => $attributes['event_category_id'],
                'team_id' => $attributes['team_id'],
            ])->id,
            'status' => CompetitionEntryStatus::Registered,
        ];
    }

    public function status(CompetitionEntryStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
