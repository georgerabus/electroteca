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
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN, so we need to recreate the table
            Schema::table('loan_requests', function (Blueprint $table) {
                $table->string('status_temp')->nullable();
            });
            
            DB::statement('UPDATE loan_requests SET status_temp = status');
            
            Schema::table('loan_requests', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            
            Schema::table('loan_requests', function (Blueprint $table) {
                $table->string('status')->default('Requested');
            });
            
            DB::statement('UPDATE loan_requests SET status = status_temp');
            
            Schema::table('loan_requests', function (Blueprint $table) {
                $table->dropColumn('status_temp');
            });
        } else {
            // MySQL/MariaDB
            DB::statement("ALTER TABLE loan_requests MODIFY COLUMN status ENUM('Requested', 'Approved', 'Picked up', 'Late', 'Return Requested', 'Returned', 'Rejected', 'Defective', 'Cancelled') DEFAULT 'Requested'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // For SQLite, we'll keep the string type (can't easily revert)
            // This is acceptable since we're just changing the allowed values
        } else {
            // MySQL/MariaDB
            DB::statement("ALTER TABLE loan_requests MODIFY COLUMN status ENUM('Requested', 'Approved', 'Picked up', 'Late', 'Returned', 'Rejected', 'Defective', 'Cancelled') DEFAULT 'Requested'");
        }
    }
};
