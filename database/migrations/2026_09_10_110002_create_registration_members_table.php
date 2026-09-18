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
        Schema::create('registration_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();

            // Provenance only — SET NULL on delete. The snapshot columns below
            // are the source of truth so this year's roster stays fixed even
            // if the master team_members list changes next year
            // (CLAUDE.md Constitution / fr-registration.md Keputusan D).
            $table->foreignId('team_member_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('member_role')->nullable();
            $table->string('gender')->nullable();
            $table->timestamps();

            $table->index('registration_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_members');
    }
};
