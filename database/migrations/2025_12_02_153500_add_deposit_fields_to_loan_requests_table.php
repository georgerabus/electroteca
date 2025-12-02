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
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('details');
            $table->decimal('damage_fee', 10, 2)->nullable()->after('deposit_amount');
            $table->decimal('refund_amount', 10, 2)->nullable()->after('damage_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->dropColumn(['deposit_amount', 'damage_fee', 'refund_amount']);
        });
    }
};
