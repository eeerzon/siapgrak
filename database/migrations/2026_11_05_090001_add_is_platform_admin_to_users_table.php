<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Platform-level role, separate from per-organization roles. Set
            // only through `php artisan siapgrak:platform-admin` — deliberately
            // not in User::$fillable (fr-public-content.md Keputusan A).
            $table->boolean('is_platform_admin')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_platform_admin');
        });
    }
};
