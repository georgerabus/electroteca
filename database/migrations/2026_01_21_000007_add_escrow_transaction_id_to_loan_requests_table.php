<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->foreignId('escrow_transaction_id')->nullable()
                ->constrained('escrow_transactions')
                ->nullOnDelete()
                ->after('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('escrow_transaction_id');
        });
    }
};
