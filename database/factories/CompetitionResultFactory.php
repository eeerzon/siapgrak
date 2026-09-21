<?php

namespace Database\Factories;

use App\Models\CompetitionEntry;
use App\Models\CompetitionResult;
use App\Models\EventCategory;
use App\Models\JudgingRound;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionResult>
 */
class CompetitionResultFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $raw = fake()->randomFloat(3, 0, 100);

        return [
            'event_category_id' => EventCategory::factory(),
            'judging_round_id' => JudgingRound::factory(),
            'competition_entry_id' => CompetitionEntry::factory(),
            'raw_score' => $raw,
            'weighted_score' => $raw,
            'penalty_score' => 0,
            'final_score' => $raw,
        ];
    }
}
