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
        Schema::table('judges', function (Blueprint $table) {
            // Plaintext by design (fr-scoring.md Keputusan B) — organizer needs
            // to redisplay it to hand to the judge on event day, not just
            // verify it once like a password.
            $table->string('access_code', 8)->nullable()->unique()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('judges', function (Blueprint $table) {
            $table->dropColumn('access_code');
        });
    }
};
