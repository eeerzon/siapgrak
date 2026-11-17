<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A round's ranking is public only after the admin publishes it (null = still private),
 * so a provisional ranking never leaks while judges are still scoring.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_rounds', function (Blueprint $table) {
            $table->timestamp('results_published_at')->nullable()->after('display_order');
        });
    }

    public function down(): void
    {
        Schema::table('competition_rounds', function (Blueprint $table) {
            $table->dropColumn('results_published_at');
        });
    }
};
