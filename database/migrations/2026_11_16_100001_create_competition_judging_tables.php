<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Judging for Info Lomba (competition_listings), separate from the organizer
 * event module (events/judging_rounds/score_sheets), which stays untouched.
 *
 * listing ─┬─ categories ─┬─ participants
 *          │              └─ rounds ─┬─ criteria
 *          │                         └─ judge assignments ── judges (owned by a user)
 *          ├─ judgings (round × participant × judge = one score sheet) ── scores, revisions
 *          └─ participant results (per round, calculated)
 *
 * Deletes are restricted wherever scores could be lost: competition history must
 * never disappear through a cascade (CLAUDE.md constitution).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_listing_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('competition_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_listing_id')->constrained()->restrictOnDelete();
            $table->foreignId('competition_category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('institution')->nullable();
            $table->string('competition_number', 20)->nullable();
            $table->unsignedInteger('performance_order')->nullable();
            $table->timestamps();

            $table->unique(['competition_category_id', 'competition_number'], 'competition_participants_number_unique');
        });

        Schema::create('competition_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('competition_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_round_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('minimum_score', 10, 3)->default(0);
            $table->decimal('maximum_score', 10, 3)->default(100);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('competition_judges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->string('name');
            $table->text('bio')->nullable();
            $table->string('access_code', 8)->unique();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('competition_judge_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_round_id')->constrained()->restrictOnDelete();
            $table->foreignId('competition_judge_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['competition_round_id', 'competition_judge_id'], 'competition_judge_assignments_unique');
        });

        // "Penjurian": one judge's score sheet for one participant in one round.
        Schema::create('competition_judgings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_listing_id')->constrained()->restrictOnDelete();
            $table->foreignId('competition_round_id')->constrained()->restrictOnDelete();
            $table->foreignId('competition_participant_id')->constrained()->restrictOnDelete();
            $table->foreignId('competition_judge_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->unsignedInteger('revision_count')->default(0);
            $table->timestamps();

            $table->unique(['competition_round_id', 'competition_participant_id', 'competition_judge_id'], 'competition_judgings_sheet_unique');
        });

        // "Nilai": one score per criterion on a judging.
        Schema::create('competition_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_judging_id')->constrained()->restrictOnDelete();
            $table->foreignId('competition_criterion_id')->constrained('competition_criteria')->restrictOnDelete();
            $table->decimal('score', 10, 3);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['competition_judging_id', 'competition_criterion_id'], 'competition_scores_criterion_unique');
        });

        // Audit trail for reopening a sent or locked judging (reason is mandatory).
        Schema::create('competition_judging_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_judging_id')->constrained()->restrictOnDelete();
            $table->foreignId('id_user')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->string('previous_status');
            $table->text('reason');
            $table->timestamps();
        });

        // "Hasil": calculated per round from sent/locked judgings only.
        Schema::create('competition_participant_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_listing_id')->constrained()->restrictOnDelete();
            $table->foreignId('competition_round_id')->constrained()->restrictOnDelete();
            $table->foreignId('competition_participant_id')->constrained(indexName: 'competition_results_participant_foreign')->restrictOnDelete();
            $table->decimal('raw_score', 10, 3);
            $table->decimal('weighted_score', 10, 3);
            $table->unsignedInteger('rank');
            $table->timestamps();

            $table->unique(['competition_round_id', 'competition_participant_id'], 'competition_results_participant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_participant_results');
        Schema::dropIfExists('competition_judging_revisions');
        Schema::dropIfExists('competition_scores');
        Schema::dropIfExists('competition_judgings');
        Schema::dropIfExists('competition_judge_assignments');
        Schema::dropIfExists('competition_judges');
        Schema::dropIfExists('competition_criteria');
        Schema::dropIfExists('competition_rounds');
        Schema::dropIfExists('competition_participants');
        Schema::dropIfExists('competition_categories');
    }
};
