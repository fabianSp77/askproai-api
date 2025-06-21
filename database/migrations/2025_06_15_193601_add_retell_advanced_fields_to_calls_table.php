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
        Schema::table('calls', function (Blueprint $table) {
            // Check which columns already exist
            $existingColumns = Schema::getColumnListing('calls');
            
            // Timestamp fields
            if (!in_array('start_timestamp', $existingColumns)) {
                $table->timestamp('start_timestamp')->nullable();
            }
            if (!in_array('end_timestamp', $existingColumns)) {
                $table->timestamp('end_timestamp')->nullable();
            }
            
            // Call details
            if (!in_array('call_type', $existingColumns)) {
                $table->string('call_type', 20)->nullable();
            }
            if (!in_array('direction', $existingColumns)) {
                $table->string('direction', 20)->nullable();
            }
            // disconnection_reason already exists
            
            // Structured transcript
            if (!in_array('transcript_object', $existingColumns)) {
                $this->addJsonColumn($table, 'transcript_object', true);
            }
            if (!in_array('transcript_with_tools', $existingColumns)) {
                $this->addJsonColumn($table, 'transcript_with_tools', true);
            }
            
            // Performance metrics
            if (!in_array('latency_metrics', $existingColumns)) {
                $this->addJsonColumn($table, 'latency_metrics', true);
            }
            if (!in_array('cost_breakdown', $existingColumns)) {
                $this->addJsonColumn($table, 'cost_breakdown', true);
            }
            if (!in_array('llm_usage', $existingColumns)) {
                $this->addJsonColumn($table, 'llm_usage', true);
            }
            
            // URLs
            if (!in_array('public_log_url', $existingColumns)) {
                $table->string('public_log_url', 500)->nullable();
            }
            
            // Dynamic variables
            if (!in_array('retell_dynamic_variables', $existingColumns)) {
                $this->addJsonColumn($table, 'retell_dynamic_variables', true);
            }
            
            // Privacy
            if (!in_array('opt_out_sensitive_data', $existingColumns)) {
                $table->boolean('opt_out_sensitive_data')->default(false);
            }
            
            // Metadata if not exists
            if (!in_array('metadata', $existingColumns)) {
                $this->addJsonColumn($table, 'metadata', true);
            }
            
            // Indexes for performance (check before adding)
            $indexes = collect(Schema::getIndexes('calls'))->pluck('name')->toArray();
            
            if (!in_array('calls_direction_index', $indexes)) {
                $table->index('direction');
            }
            if (!in_array('calls_disconnection_reason_index', $indexes)) {
                $table->index('disconnection_reason');
            }
            if (!in_array('calls_start_timestamp_end_timestamp_index', $indexes)) {
                $table->index(['start_timestamp', 'end_timestamp']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            // Drop indexes if they exist
            $indexes = collect(Schema::getIndexes('calls'))->pluck('name')->toArray();
            
            if (in_array('calls_start_timestamp_end_timestamp_index', $indexes)) {
                $table->dropIndex(['start_timestamp', 'end_timestamp']);
            }
            if (in_array('calls_disconnection_reason_index', $indexes)) {
                $table->dropIndex(['disconnection_reason']);
            }
            if (in_array('calls_direction_index', $indexes)) {
                $table->dropIndex(['direction']);
            }
            
            // Drop columns if they exist
            $existingColumns = Schema::getColumnListing('calls');
            $columnsToDelete = [];
            
            $potentialColumns = [
                'start_timestamp',
                'end_timestamp',
                'call_type',
                'direction',
                'transcript_object',
                'transcript_with_tools',
                'latency_metrics',
                'cost_breakdown',
                'llm_usage',
                'public_log_url',
                'retell_dynamic_variables',
                'opt_out_sensitive_data'
            ];
            
            foreach ($potentialColumns as $column) {
                if (in_array($column, $existingColumns)) {
                    $columnsToDelete[] = $column;
                }
            }
            
            if (!empty($columnsToDelete)) {
                $table->dropColumn($columnsToDelete);
            }
        });
    }
};