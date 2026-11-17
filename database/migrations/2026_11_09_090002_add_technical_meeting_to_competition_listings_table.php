<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_listings', function (Blueprint $table) {
            $table->date('technical_meeting_date')->nullable()->after('registration_deadline');
            $table->string('technical_meeting_location')->nullable()->after('technical_meeting_date');
        });
    }

    public function down(): void
    {
        Schema::table('competition_listings', function (Blueprint $table) {
            $table->dropColumn(['technical_meeting_date', 'technical_meeting_location']);
        });
    }
};
