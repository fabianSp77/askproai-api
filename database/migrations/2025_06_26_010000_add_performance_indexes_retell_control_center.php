<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes for calls table
        Schema::table('calls', function (Blueprint $table) {
            // Composite index for company + agent queries
            $table->index(['company_id', 'agent_id'], 'idx_calls_company_agent');
            
            // Index for phone number searches
            $table->index(['company_id', 'to_number'], 'idx_calls_company_phone');
            
            // Index for status filtering
            $table->index(['company_id', 'call_status', 'start_timestamp'], 'idx_calls_company_status_time');
            
            // Index for time-based queries
            $table->index(['company_id', 'start_timestamp'], 'idx_calls_company_time');
        });
        
        // Add indexes for retell_agents table if it exists
        if (Schema::hasTable('retell_agents')) {
            Schema::table('retell_agents', function (Blueprint $table) {
                // Index for agent lookups
                $table->index(['company_id', 'agent_id'], 'idx_retell_agents_company_agent');
                
                // Index for sync status
                $table->index(['company_id', 'sync_status'], 'idx_retell_agents_sync_status');
            });
        }
        
        // Add indexes for appointments table
        Schema::table('appointments', function (Blueprint $table) {
            // Index for call relationship
            if (!$this->indexExists('appointments', 'idx_appointments_call_id')) {
                $table->index('call_id', 'idx_appointments_call_id');
            }
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex('idx_calls_company_agent');
            $table->dropIndex('idx_calls_company_phone');
            $table->dropIndex('idx_calls_company_status_time');
            $table->dropIndex('idx_calls_company_time');
        });
        
        if (Schema::hasTable('retell_agents')) {
            Schema::table('retell_agents', function (Blueprint $table) {
                $table->dropIndex('idx_retell_agents_company_agent');
                $table->dropIndex('idx_retell_agents_sync_status');
            });
        }
        
        Schema::table('appointments', function (Blueprint $table) {
            if ($this->indexExists('appointments', 'idx_appointments_call_id')) {
                $table->dropIndex('idx_appointments_call_id');
            }
        });
    }
};