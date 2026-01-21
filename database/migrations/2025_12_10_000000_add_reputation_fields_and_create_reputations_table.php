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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('reputation_score')->default(0);
            $table->unsignedInteger('completed_loans')->default(0);
            $table->unsignedInteger('completed_orders')->default(0);
            $table->unsignedInteger('items_damaged')->default(0);
            $table->unsignedInteger('returns_on_time')->default(0);
        });

        Schema::create('reputations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('change');
            $table->string('reason');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reputations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'reputation_score',
                'completed_loans',
                'completed_orders',
                'items_damaged',
                'returns_on_time',
            ]);
        });
    }
};
