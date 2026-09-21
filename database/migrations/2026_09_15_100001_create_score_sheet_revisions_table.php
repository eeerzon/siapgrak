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
        Schema::create('score_sheet_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('score_sheet_id')->constrained()->cascadeOnDelete();

            // Nullable + SET NULL — a deleted user must never take the audit
            // trail down with them or block their own deletion (CLAUDE.md
            // Constitution, fr-score-sheet-lifecycle.md Keputusan H).
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('reason');
            $table->string('previous_status');

            // Immutable audit record — no updated_at (same pattern as the
            // planned activity_logs table in the blueprint).
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('score_sheet_revisions');
    }
};
