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
        Schema::create('judging_rounds', function (Blueprint $table) {
            $table->id();

            // Child config of a category — CASCADE, same pattern as
            // event_categories -> events and event_schedule_slots -> events.
            $table->foreignId('event_category_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('code')->nullable();
            // Never FLOAT — weights feed ResultCalculator (Sprint 11).
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('display_order')->default(0);

            $table->timestamps();

            $table->index(['event_category_id', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('judging_rounds');
    }
};
