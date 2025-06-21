<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip for SQLite
        if (config('database.default') === 'sqlite') {
            return;
        }

        // Check if we need to fix the auto-increment issue
        try {
            // Try to get the table info
            $tableInfo = DB::select("SHOW COLUMNS FROM staff_service_assignments WHERE Field = 'id'");
            
            if (!empty($tableInfo) && $tableInfo[0]->Extra !== 'auto_increment') {
                // The id column exists but is not auto-incrementing, we need to fix it
                
                // Get existing data
                $existingData = DB::table('staff_service_assignments')->get();
                
                // Get foreign key info
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'staff_service_assignments' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                // Drop foreign keys
                Schema::table('staff_service_assignments', function (Blueprint $table) use ($foreignKeys) {
                    foreach ($foreignKeys as $fk) {
                        $table->dropForeign($fk->CONSTRAINT_NAME);
                    }
                });
                
                // Drop and recreate the table
                Schema::drop('staff_service_assignments');
                
                $this->createTableIfNotExists('staff_service_assignments', function (Blueprint $table) {
                    $table->id(); // This creates an auto-incrementing BIGINT UNSIGNED primary key
                    $table->char('staff_id', 36);
                    $table->unsignedBigInteger('calcom_event_type_id');
                    $table->timestamps();
                    
                    // Add foreign keys
                    $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
                    $table->foreign('calcom_event_type_id')->references('id')->on('calcom_event_types')->onDelete('cascade');
                    
                    // Add unique constraint
                    $table->unique(['staff_id', 'calcom_event_type_id'], 'unique_staff_event');
                    
                    // Add indexes
                    $table->index('staff_id');
                    $table->index('calcom_event_type_id');
                });
                
                // Restore data if any existed
                if ($existingData->count() > 0) {
                    foreach ($existingData as $row) {
                        DB::table('staff_service_assignments')->insert([
                            'staff_id' => $row->staff_id,
                            'calcom_event_type_id' => $row->calcom_event_type_id,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            // If the table doesn't exist, create it
            if (!Schema::hasTable('staff_service_assignments')) {
                $this->createTableIfNotExists('staff_service_assignments', function (Blueprint $table) {
                    $table->id();
                    $table->char('staff_id', 36);
                    $table->unsignedBigInteger('calcom_event_type_id');
                    $table->timestamps();
                    
                    $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
                    $table->foreign('calcom_event_type_id')->references('id')->on('calcom_event_types')->onDelete('cascade');
                    
                    $table->unique(['staff_id', 'calcom_event_type_id'], 'unique_staff_event');
                    
                    $table->index('staff_id');
                    $table->index('calcom_event_type_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse as we're fixing the table structure
        if (config('database.default') === 'sqlite') {
            return;
        }
    }
};
