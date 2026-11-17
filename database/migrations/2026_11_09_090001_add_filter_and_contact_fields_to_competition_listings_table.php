<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competition_listings', function (Blueprint $table) {
            // CompetitionLevel values; one competition often opens several levels.
            $table->json('levels')->nullable()->after('end_date');
            // One of config('siapgrak.provinces'), used by the public filter.
            $table->string('province')->nullable()->after('location');
            $table->date('registration_deadline')->nullable()->after('levels');
            // [{name, phone}], for events that register through a contact person.
            $table->json('contact_persons')->nullable()->after('external_url');

            $table->index('province');
        });
    }

    public function down(): void
    {
        Schema::table('competition_listings', function (Blueprint $table) {
            $table->dropIndex(['province']);
            $table->dropColumn(['levels', 'province', 'registration_deadline', 'contact_persons']);
        });
    }
};
