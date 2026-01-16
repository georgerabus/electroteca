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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            
            // Payment details
            $table->string('payment_id')->unique(); // External payment ID from gateway
            $table->string('gateway')->default('paddle'); // 'paddle', 'paypal'
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            
            // Status tracking
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled'])
                  ->default('pending');
            
            // Metadata
            $table->json('metadata')->nullable(); // Store additional info from gateway
            $table->text('error_message')->nullable(); // If payment failed
            
            // Timestamps
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index('user_id');
            $table->index('order_id');
            $table->index('status');
            $table->index('gateway');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
