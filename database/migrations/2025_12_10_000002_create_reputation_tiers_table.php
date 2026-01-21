<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reputation_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('min_score');
            $table->unsignedTinyInteger('discount_percent')->default(0);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('reputation_tiers')->insert([
            [
                'name' => 'Starter',
                'min_score' => 0,
                'discount_percent' => 0,
                'description' => 'New members start here.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bronze',
                'min_score' => 20,
                'discount_percent' => 5,
                'description' => 'Consistent returns unlock a small discount.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Silver',
                'min_score' => 50,
                'discount_percent' => 10,
                'description' => 'Reliable members earn better pricing.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gold',
                'min_score' => 80,
                'discount_percent' => 15,
                'description' => 'Top-tier trust comes with the best discount.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reputation_tiers');
    }
};
