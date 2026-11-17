<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The content owner column is called id_user (requested naming). It still
 * references users.id; each admin manages only their own rows.
 */
return new class extends Migration
{
    private const TABLES = ['competition_listings', 'agenda_items', 'community_posts'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->renameColumn('created_by', 'id_user'));
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->renameColumn('id_user', 'created_by'));
        }
    }
};
