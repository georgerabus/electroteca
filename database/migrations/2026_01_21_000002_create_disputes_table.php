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
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('initiator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('respondent_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['open', 'awaiting_resolution', 'resolved', 'closed'])->default('open');
            $table->string('reason'); // item_damaged, not_as_described, not_received, other
            $table->json('evidence_urls')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolved_at')->nullable();
            $table->boolean('is_appealed')->default(false);
            $table->text('appeal_notes')->nullable();
            $table->json('appeal_evidence_urls')->nullable();
            $table->dateTime('appeal_resolved_at')->nullable();
            $table->string('final_resolution')->nullable(); // initiator_wins, respondent_wins, compromise
            $table->decimal('damage_claim_amount', 10, 2)->nullable();
            $table->decimal('approved_deduction_amount', 10, 2)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
