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
        Schema::create('judges', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();

            // NOT in the original blueprint — added to close a tenant-isolation
            // gap (fr-judging-setup.md Keputusan A, confirmed with Ersa
            // 2026-09-14). Nullable at schema level (matches teams.organization_id
            // Sprint 2 pattern) but required at validation. RESTRICT: preserve
            // judge identity/history, same reasoning as teams.
            $table->foreignId('organization_id')->nullable()->constrained()->restrictOnDelete();

            // A judge may not have an account yet.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            // Column exists per blueprint; upload pipeline deferred (same
            // reasoning as events.poster_path / organizations.logo_path).
            $table->string('photo_path')->nullable();
            $table->text('bio')->nullable();
            $table->string('status')->default('active');

            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('judges');
    }
};
