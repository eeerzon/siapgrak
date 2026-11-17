<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competition_listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('excerpt', 500)->nullable();
            $table->text('body')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('organizer_name')->nullable();
            $table->string('location')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // true = event we run: transactions happen on Kreenconnect, reached
            // via kreen_slug. Siapgrak stores no transaction data (ADR-001).
            $table->boolean('is_managed')->default(false);
            $table->string('kreen_slug')->nullable()->unique();
            // Only for events run by someone else.
            $table->string('external_url', 2048)->nullable();

            $table->string('status')->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_listings');
    }
};
