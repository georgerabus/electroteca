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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('escrow_amount', 10, 2)->default(0);
            $table->enum('escrow_status', ['none', 'held', 'released', 'partially_deducted', 'cancelled'])->default('none');
            $table->integer('inspection_period_days')->default(7);
            $table->dateTime('return_deadline')->nullable();
            $table->text('escrow_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeignIdFor('seller_id');
            $table->dropColumn([
                'seller_id',
                'escrow_amount',
                'escrow_status',
                'inspection_period_days',
                'return_deadline',
                'escrow_notes',
            ]);
        });
    }
};
