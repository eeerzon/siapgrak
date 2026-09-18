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
        Schema::create('competition_entries', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();

            // All RESTRICT — an entry is competition history, never cascaded
            // away by deleting its parents (CLAUDE.md Constitution).
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('event_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('registration_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('team_id')->constrained()->restrictOnDelete();

            $table->string('competition_number')->nullable();
            $table->unsignedInteger('performance_order')->nullable();
            $table->string('status')->default('registered');
            $table->dateTime('checked_in_at')->nullable();

            $table->timestamps();

            // Numbers are unique per category, not per event (fr-competition-entry.md Keputusan E).
            $table->unique(['event_category_id', 'competition_number']);
            $table->index(['event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competition_entries');
    }
};
