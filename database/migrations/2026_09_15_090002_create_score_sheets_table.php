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
        Schema::create('score_sheets', function (Blueprint $table) {
            $table->id();

            // RESTRICT on all three — a score sheet is competition history,
            // never allow the round/entry/judge it references to vanish out
            // from under it (fr-scoring.md Keputusan H, CLAUDE.md Constitution).
            $table->foreignId('judging_round_id')->constrained()->restrictOnDelete();
            $table->foreignId('competition_entry_id')->constrained()->restrictOnDelete();
            $table->foreignId('judge_id')->constrained()->restrictOnDelete();

            // Only 'draft' is reachable this sprint — submit/lock/reopen are
            // Sprint 10 (fr-scoring.md Keputusan G). Columns below are added
            // now to match the full blueprint shape and avoid another ALTER
            // TABLE later.
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->unsignedInteger('revision_count')->default(0);

            $table->timestamps();

            // Explicit short name — Sprint 8 already hit MySQL's 64-char
            // identifier limit with a similarly-shaped 3-column unique.
            $table->unique(['judging_round_id', 'competition_entry_id', 'judge_id'], 'score_sheets_unique');
            $table->index('judge_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('score_sheets');
    }
};
