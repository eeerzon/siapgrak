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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('public_id')->unique();

            // Nullable at schema level to match the long-term design (a team
            // may one day exist without an institutional organization), but
            // Sprint 2 validation requires it on every create — see
            // fr-team-directory.md "Keputusan Desain Kritis". RESTRICT (not
            // CASCADE) so an organization can't be hard-deleted while it
            // still owns teams — historical team identity is preserved.
            $table->foreignId('organization_id')->nullable()->constrained()->restrictOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('province_code')->nullable();
            $table->string('city_code')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
