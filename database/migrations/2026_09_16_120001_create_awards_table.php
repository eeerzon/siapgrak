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
        Schema::create('awards', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_category_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('name');
            // Free-text, not an enum — real-world award types are too varied
            // to enumerate (fr-awards-finalization.md).
            $table->string('award_type');

            // SET NULL, not RESTRICT/CASCADE — an award's historical record
            // (the winner's name is already in `name`) must survive even if
            // the underlying entry/member disappears (Keputusan of this file).
            $table->foreignId('competition_entry_id')->nullable()->constrained()->nullOnDelete();

            // Schema-only this sprint — not exposed in the form yet
            // (Keputusan F).
            $table->foreignId('registration_member_id')->nullable()->constrained()->nullOnDelete();

            $table->string('source')->default('manual');
            $table->string('external_reference')->nullable();
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
        Schema::dropIfExists('awards');
    }
};
