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
        Schema::create('dispute_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // created, evidence_submitted, resolution_submitted, resolved, appealed, etc
            $table->text('description');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('data')->nullable(); // Additional metadata
            $table->timestamps();

            $table->index('dispute_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dispute_histories');
    }
};
