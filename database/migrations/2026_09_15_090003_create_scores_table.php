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
        Schema::create('scores', function (Blueprint $table) {
            $table->id();

            // Scores don't outlive their sheet — no UI ever deletes a sheet
            // on its own, so CASCADE here is inert in practice, just tidy.
            $table->foreignId('score_sheet_id')->constrained()->cascadeOnDelete();

            // RESTRICT — protects stored scores from vanishing if a criteria
            // is deleted; the real prevention is the app-level guard in
            // JudgingCriteriaController::destroy (fr-scoring.md Keputusan H).
            $table->foreignId('judging_criteria_id')->constrained('judging_criteria')->restrictOnDelete();

            // Never FLOAT (CLAUDE.md Aturan Kritis).
            $table->decimal('score', 10, 3);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['score_sheet_id', 'judging_criteria_id'], 'scores_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
