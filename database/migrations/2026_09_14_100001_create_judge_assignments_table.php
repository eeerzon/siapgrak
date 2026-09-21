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
        Schema::create('judge_assignments', function (Blueprint $table) {
            $table->id();

            // No event_category_id column (fr-judge-assignment.md Keputusan A)
            // — it's 100% derivable via judging_round.event_category_id, and
            // storing it too would be a second source of truth that could drift.
            $table->foreignId('judging_round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained()->restrictOnDelete();

            // NULL = judge scores the whole round. CASCADE (not SET NULL,
            // Keputusan D) — deleting the criterion must not silently widen a
            // specific assignment into a whole-round one.
            $table->foreignId('judging_criteria_id')->nullable()->constrained('judging_criteria')->cascadeOnDelete();

            $table->string('status')->default('active');
            $table->timestamps();

            // Defense-in-depth for the non-NULL case only — MySQL/MariaDB
            // treats NULL as always distinct, so this does NOT prevent a judge
            // getting two "whole round" (criteria_id NULL) assignments in the
            // same round. That case is caught at the application level
            // (Keputusan B) — see StoreJudgeAssignmentRequest.
            // Explicit short name: Laravel's auto-generated name for this
            // column combination exceeds MySQL's 64-char identifier limit.
            $table->unique(['judging_round_id', 'judge_id', 'judging_criteria_id'], 'judge_assignments_unique');
            $table->index('judge_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('judge_assignments');
    }
};
