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
        Schema::create('judging_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('judging_round_id')->constrained()->cascadeOnDelete();

            // Self-referential, max nesting depth 2 (root + subcriteria),
            // enforced in JudgingService — not a DB constraint (fr-judging-setup.md
            // Keputusan D). RESTRICT: a parent can't vanish while children point
            // to it (delete children first).
            $table->foreignId('parent_id')->nullable()->constrained('judging_criteria')->restrictOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            // Never FLOAT (CLAUDE.md Aturan Kritis).
            $table->decimal('minimum_score', 10, 3);
            $table->decimal('maximum_score', 10, 3);
            $table->unsignedInteger('display_order')->default(0);
            $table->string('status')->default('active');

            $table->timestamps();

            $table->index(['judging_round_id', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('judging_criteria');
    }
};
