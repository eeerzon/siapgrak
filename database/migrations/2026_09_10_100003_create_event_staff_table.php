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
        Schema::create('event_staff', function (Blueprint $table) {
            $table->id();

            // Both CASCADE: a staff row is an assignment link, not history —
            // if the event or the user is gone, the assignment is meaningless.
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Recorded but NOT yet an authorization source in Sprint 3 — see
            // fr-event-category.md Keputusan A. Cast to EventStaffRole in the model.
            $table->string('role');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_staff');
    }
};
