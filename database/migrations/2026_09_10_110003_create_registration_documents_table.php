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
        Schema::create('registration_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();

            $table->string('document_type', 100);
            $table->string('title')->nullable();
            // Path on the PRIVATE 'local' disk (storage/app/private). Never
            // exposed by a permanent public URL — download goes through a
            // Policy-checked route. See fr-registration.md Keputusan E.
            $table->string('file_path');
            // Per-document review deferred (Keputusan F) — column exists, no
            // approve/reject endpoint in Sprint 4.
            $table->string('status')->default('uploaded');
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index('registration_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_documents');
    }
};
