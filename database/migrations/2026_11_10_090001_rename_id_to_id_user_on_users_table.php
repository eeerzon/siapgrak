<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The users primary key is named id_user, matching the id_user ownership columns.
 * Foreign keys pointing at users follow the rename (MySQL 8 and SQLite >= 3.26
 * rewrite the references), so the referencing columns keep their names.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('id', 'id_user');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('id_user', 'id');
        });
    }
};
