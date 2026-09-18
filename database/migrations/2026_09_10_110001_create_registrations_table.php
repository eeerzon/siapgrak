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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();

            // RESTRICT everywhere: an event / category / team that already has
            // a registration is competition history — it must not be hard
            // deleted (CLAUDE.md Constitution). fr-registration.md RULE-12.
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('event_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('team_id')->constrained()->restrictOnDelete();

            // Admin-entry: the staff who entered it. Kept generic so future
            // self-registration fills this with the participant's user without
            // a schema change (ADR-002 / fr-registration.md Keputusan A).
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status')->default('draft');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();

            $table->timestamps();

            // One team can register to a given category at most once.
            $table->unique(['event_category_id', 'team_id']);
            $table->index(['event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
