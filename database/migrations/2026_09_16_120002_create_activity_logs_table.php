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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Nullable + SET NULL — same reasoning as score_sheet_revisions:
            // a deleted user must never take the audit trail down with them.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('action');
            // Polymorphic-ish but deliberately NOT a real polymorphic FK —
            // subject can be any model type, no constraint needed for an
            // append-only audit trail.
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();

            // Immutable — no updated_at (fr-awards-finalization.md).
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
