<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Helper function to drop index if exists
        $dropIfExists = function(string $table, string $index) {
            try {
                $exists = DB::select("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index]);
                if (!empty($exists)) {
                    DB::statement("ALTER TABLE `$table` DROP INDEX `$index`");
                    echo "Dropped index: $index\n";
                }
            } catch (\Throwable $e) {
                // Index doesn't exist, that's fine
            }
        };

        // Count current indexes
        $indexCount = count(DB::select("SHOW INDEX FROM `calls`"));
        echo "Current index count on calls table: $indexCount\n";
        
        if ($indexCount > 60) {
            echo "Too many indexes detected, cleaning up duplicates...\n";
            
            // Remove duplicate and redundant indexes
            // Keep the most specific/useful ones, remove general ones
            
            // Remove duplicate phone indexes (keep composite ones)
            $dropIfExists('calls', 'calls_phone_number_index');
            $dropIfExists('calls', 'calls_from_number_index');
            $dropIfExists('calls', 'calls_to_number_index');
            $dropIfExists('calls', 'idx_phone_status'); // This one was causing the error
            
            // Remove single column indexes where composites exist
            $dropIfExists('calls', 'calls_status_index');
            $dropIfExists('calls', 'calls_call_status_index');
            $dropIfExists('calls', 'calls_created_at_index');
            $dropIfExists('calls', 'calls_company_id_index');
            $dropIfExists('calls', 'calls_branch_id_index');
            $dropIfExists('calls', 'calls_customer_id_index');
            
            // Remove less useful single indexes
            $dropIfExists('calls', 'calls_direction_index');
            $dropIfExists('calls', 'calls_cost_index');
            $dropIfExists('calls', 'calls_duration_sec_index');
            $dropIfExists('calls', 'calls_recording_url_index');
            $dropIfExists('calls', 'calls_appointment_id_index');
            $dropIfExists('calls', 'calls_conversation_id_index');
            $dropIfExists('calls', 'calls_external_id_index');
            $dropIfExists('calls', 'calls_sentiment_index');
            $dropIfExists('calls', 'calls_urgency_level_index');
            $dropIfExists('calls', 'calls_metadata_index');
            $dropIfExists('calls', 'calls_disconnection_reason_index');
            $dropIfExists('calls', 'calls_appointment_made_index');
            
            // Remove duplicate retell_call_id unique indexes (keep only one)
            $dropIfExists('calls', 'calls_retell_call_id_unique_2');
            $dropIfExists('calls', 'calls_retell_call_id_unique_3');
            $dropIfExists('calls', 'retell_call_id_unique');
            
            // Remove duplicate call_id indexes
            $dropIfExists('calls', 'calls_call_id_unique_2');
            $dropIfExists('calls', 'calls_call_id_unique_3');
            
            // Now ensure we have the essential indexes
            
            // Unique constraint for idempotency (most important)
            try {
                $hasRetellUnique = DB::select("SHOW INDEX FROM `calls` WHERE Key_name = 'calls_retell_call_id_unique'");
                if (empty($hasRetellUnique)) {
                    DB::statement("ALTER TABLE `calls` ADD UNIQUE INDEX `calls_retell_call_id_unique` (`retell_call_id`)");
                    echo "Added unique index for retell_call_id\n";
                }
            } catch (\Throwable $e) {
                echo "Note: retell_call_id unique index might already exist\n";
            }
            
            // Composite indexes for common queries (more efficient than single column)
            try {
                DB::statement("ALTER TABLE `calls` ADD INDEX `idx_company_status_created` (`company_id`, `call_status`, `created_at`)");
                echo "Added composite index: company_status_created\n";
            } catch (\Throwable $e) {}
            
            try {
                DB::statement("ALTER TABLE `calls` ADD INDEX `idx_branch_created` (`branch_id`, `created_at`)");
                echo "Added composite index: branch_created\n";
            } catch (\Throwable $e) {}
            
            try {
                DB::statement("ALTER TABLE `calls` ADD INDEX `idx_customer_created` (`customer_id`, `created_at`)");
                echo "Added composite index: customer_created\n";
            } catch (\Throwable $e) {}
            
            // Foreign key indexes (needed for relationships)
            try {
                DB::statement("ALTER TABLE `calls` ADD INDEX `idx_company` (`company_id`)");
            } catch (\Throwable $e) {}
            
            try {
                DB::statement("ALTER TABLE `calls` ADD INDEX `idx_branch` (`branch_id`)");
            } catch (\Throwable $e) {}
            
            try {
                DB::statement("ALTER TABLE `calls` ADD INDEX `idx_customer` (`customer_id`)");
            } catch (\Throwable $e) {}
        }
        
        // Final count
        $finalCount = count(DB::select("SHOW INDEX FROM `calls`"));
        echo "Final index count on calls table: $finalCount\n";
        
        if ($finalCount > 64) {
            echo "WARNING: Still too many indexes ($finalCount). Manual cleanup may be needed.\n";
        }
    }

    public function down(): void
    {
        // We don't recreate dropped indexes on rollback
        // They were redundant anyway
    }
};