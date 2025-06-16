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
        Schema::table('calls', function (Blueprint $table) {
            // Check which columns already exist
            $existingColumns = Schema::getColumnListing('calls');
            
            // Timestamp fields
            if (!in_array('start_timestamp', $existingColumns)) {
                $table->timestamp('start_timestamp')->nullable()->after('created_at');
            }
            if (!in_array('end_timestamp', $existingColumns)) {
                $table->timestamp('end_timestamp')->nullable()->after('start_timestamp');
            }
            
            // Call details
            if (!in_array('call_type', $existingColumns)) {
                $table->string('call_type', 20)->nullable()->after('call_status');
            }
            if (!in_array('direction', $existingColumns)) {
                $table->string('direction', 20)->nullable()->after('call_type');
            }
            // disconnection_reason already exists
            
            // Structured transcript
            if (!in_array('transcript_object', $existingColumns)) {
                $table->json('transcript_object')->nullable()->after('transcript');
            }
            if (!in_array('transcript_with_tools', $existingColumns)) {
                $table->json('transcript_with_tools')->nullable()->after('transcript_object');
            }
            
            // Performance metrics
            if (!in_array('latency_metrics', $existingColumns)) {
                $table->json('latency_metrics')->nullable()->after('analysis');
            }
            if (!in_array('cost_breakdown', $existingColumns)) {
                $table->json('cost_breakdown')->nullable()->after('cost_cents');
            }
            if (!in_array('llm_usage', $existingColumns)) {
                $table->json('llm_usage')->nullable()->after('cost_breakdown');
            }
            
            // URLs
            if (!in_array('public_log_url', $existingColumns)) {
                $table->string('public_log_url', 500)->nullable()->after('audio_url');
            }
            
            // Dynamic variables
            if (!in_array('retell_dynamic_variables', $existingColumns)) {
                $table->json('retell_dynamic_variables')->nullable();
            }
            
            // Privacy
            if (!in_array('opt_out_sensitive_data', $existingColumns)) {
                $table->boolean('opt_out_sensitive_data')->default(false);
            }
            
            // Metadata if not exists
            if (!in_array('metadata', $existingColumns)) {
                $table->json('metadata')->nullable();
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