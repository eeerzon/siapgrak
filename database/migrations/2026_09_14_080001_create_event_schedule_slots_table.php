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
        Schema::create('event_schedule_slots', function (Blueprint $table) {
            $table->id();

            // CASCADE: a slot is child configuration of its event, same as
            // event_categories — not competition history like registrations/
            // entries (fr-schedule.md Keputusan E).
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            // Both optional and SET NULL: a general slot (break, ceremony) is
            // tied to neither; if the linked category/entry later goes away
            // the slot survives, just detached (Keputusan D).
            $table->foreignId('event_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('competition_entry_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->string('venue_area')->nullable();
            $table->string('slot_type');
            $table->unsignedInteger('display_order')->default(0);

            $table->timestamps();

            $table->index(['event_id', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_schedule_slots');
    }
};
