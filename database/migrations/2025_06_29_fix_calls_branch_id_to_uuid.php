<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip this migration in testing environment (SQLite)
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }
        
        // First check if we need to do anything
        $currentType = DB::select("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                                  WHERE TABLE_NAME = 'calls' 
                                  AND COLUMN_NAME = 'branch_id' 
                                  AND TABLE_SCHEMA = DATABASE()")[0]->DATA_TYPE ?? null;
        
        if ($currentType === 'char' || $currentType === 'varchar') {
            // Already UUID type, nothing to do
            return;
        }
        
        Schema::table('calls', function (Blueprint $table) {
            // Drop the old column
            $table->dropColumn('branch_id');
        });
        
        Schema::table('calls', function (Blueprint $table) {
            // Add new UUID column
            $table->uuid('branch_id')->nullable()->after('company_id');
            
            // Add index for performance
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            // Drop the UUID column
            $table->dropColumn('branch_id');
        });
        
        Schema::table('calls', function (Blueprint $table) {
            // Restore the bigint column
            $table->unsignedBigInteger('branch_id')->nullable()->after('company_id');
            
            // Restore index
            $table->index('branch_id');
        });
    }
};