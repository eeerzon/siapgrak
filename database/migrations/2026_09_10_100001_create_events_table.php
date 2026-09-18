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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();

            // NOT NULL — there is no "event without an organizer". RESTRICT so
            // an organization can't be hard-deleted while it still owns events
            // (Constitution: preserve competition history). See fr-event-category.md.
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description', 500)->nullable();
            $table->text('description')->nullable();
            // Column exists per blueprint; upload pipeline deferred (fr-event-category.md Keputusan C).
            $table->string('poster_path')->nullable();

            $table->date('start_date');
            $table->date('end_date');
            $table->dateTime('registration_start_at')->nullable();
            $table->dateTime('registration_end_at')->nullable();

            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->string('province_code')->nullable();
            $table->string('city_code')->nullable();

            // Stored as string + cast to EventStatus in the model (same as
            // organizations.status/type) — avoids costly ALTER on a MySQL ENUM
            // when values change. 7 states, see EventStatus.
            $table->string('status')->default('draft');
            $table->dateTime('published_at')->nullable();

            // Trace of who created it. SET NULL (not RESTRICT) so it never
            // blocks a user removal; users aren't hard-deleted in practice.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
