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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'payment_method')) {
                $table->string('payment_method')->default('paddle')->comment('paddle, wallet, or hybrid');
            }
            if (!Schema::hasColumn('payments', 'paddle_amount')) {
                $table->decimal('paddle_amount', 10, 2)->nullable()->comment('Amount paid via Paddle');
            }
            if (!Schema::hasColumn('payments', 'wallet_amount')) {
                $table->decimal('wallet_amount', 10, 2)->nullable()->comment('Amount paid via wallet credits');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('payments', 'paddle_amount')) {
                $table->dropColumn('paddle_amount');
            }
            if (Schema::hasColumn('payments', 'wallet_amount')) {
                $table->dropColumn('wallet_amount');
            }
        });
    }
};
