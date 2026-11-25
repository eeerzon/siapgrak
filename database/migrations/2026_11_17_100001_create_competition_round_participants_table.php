<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Who takes part in a round after the first one (e.g. the teams that made the final).
 * The first round of a category has no rows here: everyone in the category takes part.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_round_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_round_id')->constrained('competition_rounds')->restrictOnDelete();
            $table->foreignId('competition_participant_id')->constrained('competition_participants', indexName: 'competition_round_participants_participant_foreign')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['competition_round_id', 'competition_participant_id'], 'competition_round_participants_unique');
        });

        // Rounds after the first that were already judged keep exactly the teams that have a sheet there,
        // so no existing score drops out of a round.
        $now = now();
        $rounds = DB::table('competition_rounds')->orderBy('display_order')->orderBy('id')->get()->groupBy('competition_category_id');

        foreach ($rounds as $categoryRounds) {
            foreach ($categoryRounds->slice(1) as $round) {
                $participantIds = DB::table('competition_judgings')
                    ->where('competition_round_id', $round->id)
                    ->distinct()
                    ->pluck('competition_participant_id');

                DB::table('competition_round_participants')->insert($participantIds->map(fn ($participantId) => [
                    'competition_round_id' => $round->id,
                    'competition_participant_id' => $participantId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_round_participants');
    }
};
