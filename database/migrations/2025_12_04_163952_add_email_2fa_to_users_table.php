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
            $table->string('email_2fa_code')->nullable()->after('two_factor_confirmed_at');
            $table->timestamp('email_2fa_expires_at')->nullable()->after('email_2fa_code');
            $table->timestamp('email_2fa_verified_at')->nullable()->after('email_2fa_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_2fa_code',
                'email_2fa_expires_at',
                'email_2fa_verified_at',
            ]);
        });
    }
};
