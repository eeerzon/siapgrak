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
        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();

            // A category has no meaning without its event. CASCADE only ever
            // fires for an event that is allowed to be deleted (Sprint 3 has
            // no hard-delete route for events).
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedInteger('min_members')->nullable();
            $table->unsignedInteger('max_members')->nullable();
            // Optional per-category override of the event-level registration window.
            $table->dateTime('registration_start_at')->nullable();
            $table->dateTime('registration_end_at')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'display_order']);
            // code nullable: MySQL allows many rows with code = NULL per event
            // (categories without a code), but named codes are unique per event.
            $table->unique(['event_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_categories');
    }
};
