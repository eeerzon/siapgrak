<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('competition_results', function (Blueprint $table) {
            $table->id();

            // CASCADE across the board — unlike score_sheets/scores this is
            // pure derived data, always recalculable from scores, never
            // historical evidence that must be protected (fr-result-calculator.md).
            $table->foreignId('event_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judging_round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_entry_id')->constrained()->cascadeOnDelete();

            // Never FLOAT (CLAUDE.md Aturan Kritis).
            $table->decimal('raw_score', 10, 3);
            $table->decimal('weighted_score', 10, 3);
            $table->decimal('penalty_score', 10, 3)->default(0);
            $table->decimal('final_score', 10, 3);

            $table->unsignedInteger('rank')->nullable();

            // Only 'draft' is reachable this sprint — finalize/publish are
            // Sprint 12 (fr-result-calculator.md Keputusan G).
            $table->string('status')->default('draft');
            $table->timestamp('finalized_at')->nullable();

            $table->timestamps();

            // Explicit short name — Sprint 8/9 already hit MySQL's 64-char
            // identifier limit with similarly-shaped 3-column uniques.
            $table->unique(['event_category_id', 'judging_round_id', 'competition_entry_id'], 'competition_results_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competition_results');
    }
};
