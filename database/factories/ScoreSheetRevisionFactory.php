<?php

namespace Database\Factories;

use App\Models\ScoreSheet;
use App\Models\ScoreSheetRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoreSheetRevision>
 */
class ScoreSheetRevisionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'score_sheet_id' => ScoreSheet::factory(),
            'requested_by' => User::factory(),
            'reason' => fake()->sentence(),
            'previous_status' => 'submitted',
        ];
    }
}
