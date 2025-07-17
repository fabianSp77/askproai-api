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
        // Diese Migration ist nur für MySQL relevant
        if (config('database.default') !== 'mysql') {
            return;
        }
        
        // Temporarily disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        try {
            Schema::table('appointments', function (Blueprint $table) {
                // Drop existing foreign key constraints if they exist
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'appointments' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                    AND COLUMN_NAME IN ('branch_id', 'staff_id')
                ");
                
                foreach ($foreignKeys as $key) {
                    try {
                        $table->dropForeign([$key->CONSTRAINT_NAME]);
                    } catch (\Exception $e) {
                        // Ignore if foreign key doesn't exist
                    }
                }
                
                // Backup data before changing column types
                DB::statement("
                    CREATE TEMPORARY TABLE appointments_backup AS 
                    SELECT id, branch_id, staff_id 
                    FROM appointments 
                    WHERE branch_id IS NOT NULL OR staff_id IS NOT NULL
                ");
                
                // Set columns to NULL to avoid data loss
                DB::statement("UPDATE appointments SET branch_id = NULL WHERE branch_id IS NOT NULL");
                DB::statement("UPDATE appointments SET staff_id = NULL WHERE staff_id IS NOT NULL");
                
                // Change column types to match the referenced tables
                // branches.id and staff.id are char(36) UUIDs
                $table->uuid('branch_id')->nullable()->change();
                $table->uuid('staff_id')->nullable()->change();
            });
            
            // Restore data if possible (only valid UUIDs)
            DB::statement("
                UPDATE appointments a
                JOIN appointments_backup ab ON a.id = ab.id
                JOIN branches b ON b.id = ab.branch_id
                SET a.branch_id = ab.branch_id
                WHERE ab.branch_id IS NOT NULL
            ");
            
            DB::statement("
                UPDATE appointments a
                JOIN appointments_backup ab ON a.id = ab.id
                JOIN staff s ON s.id = ab.staff_id
                SET a.staff_id = ab.staff_id
                WHERE ab.staff_id IS NOT NULL
            ");
            
            // Drop temporary table
            DB::statement("DROP TEMPORARY TABLE IF EXISTS appointments_backup");
            
            // Re-add foreign key constraints
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                $table->foreign('staff_id')->references('id')->on('staff')->onDelete('set null');
            });
            
        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Diese Migration ist nur für MySQL relevant
        if (config('database.default') !== 'mysql') {
            return;
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        try {
            Schema::table('appointments', function (Blueprint $table) {
                // Drop foreign keys
                try {
                    $table->dropForeign(['branch_id']);
                    $table->dropForeign(['staff_id']);
                } catch (\Exception $e) {
                    // Ignore if they don't exist
                }
                
                // Change back to bigint
                $table->unsignedBigInteger('branch_id')->nullable()->change();
                $table->char('staff_id', 36)->nullable()->change();
            });
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
};
