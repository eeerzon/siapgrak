<?php

namespace Database\Factories;

use App\Models\CompetitionEntry;
use App\Models\Judge;
use App\Models\JudgingRound;
use App\Models\ScoreSheet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoreSheet>
 */
class ScoreSheetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judging_round_id' => JudgingRound::factory(),
            'competition_entry_id' => CompetitionEntry::factory(),
            'judge_id' => Judge::factory(),
        ];
    }
}
