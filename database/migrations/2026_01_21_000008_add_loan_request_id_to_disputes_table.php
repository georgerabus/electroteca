<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->foreignId('loan_request_id')->nullable()
                ->constrained('loan_requests')
                ->nullOnDelete()
                ->after('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loan_request_id');
        });
    }
};
