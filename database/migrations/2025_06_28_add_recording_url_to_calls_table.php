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
            // Add recording_url column if it doesn't exist
            if (!Schema::hasColumn('calls', 'recording_url')) {
                $table->string('recording_url')->nullable()->after('audio_url');
            }
            
            // Add other missing columns from the model
            if (!Schema::hasColumn('calls', 'call_status')) {
                $table->string('call_status')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'retell_agent_id')) {
                $table->string('retell_agent_id')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'transcription_id')) {
                $table->string('transcription_id')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'call_type')) {
                $table->string('call_type')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'video_url')) {
                $table->string('video_url')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'cost')) {
                $table->decimal('cost', 10, 4)->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'appointment_id')) {
                $table->bigInteger('appointment_id')->unsigned()->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'staff_id')) {
                $table->bigInteger('staff_id')->unsigned()->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'analysis')) {
                $this->addJsonColumn($table, 'analysis', true);
            }
            
            if (!Schema::hasColumn('calls', 'raw_data')) {
                $this->addJsonColumn($table, 'raw_data', true);
            }
            
            if (!Schema::hasColumn('calls', 'notes')) {
                $table->text('notes')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'metadata')) {
                $this->addJsonColumn($table, 'metadata', true);
            }
            
            if (!Schema::hasColumn('calls', 'tags')) {
                $this->addJsonColumn($table, 'tags', true);
            }
            
            if (!Schema::hasColumn('calls', 'status')) {
                $table->string('status')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'start_timestamp')) {
                $table->bigInteger('start_timestamp')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'end_timestamp')) {
                $table->bigInteger('end_timestamp')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'direction')) {
                $table->string('direction')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'transcript_object')) {
                $this->addJsonColumn($table, 'transcript_object', true);
            }
            
            if (!Schema::hasColumn('calls', 'transcript_with_tools')) {
                $this->addJsonColumn($table, 'transcript_with_tools', true);
            }
            
            if (!Schema::hasColumn('calls', 'latency_metrics')) {
                $this->addJsonColumn($table, 'latency_metrics', true);
            }
            
            if (!Schema::hasColumn('calls', 'cost_breakdown')) {
                $this->addJsonColumn($table, 'cost_breakdown', true);
            }
            
            if (!Schema::hasColumn('calls', 'llm_usage')) {
                $this->addJsonColumn($table, 'llm_usage', true);
            }
            
            if (!Schema::hasColumn('calls', 'retell_dynamic_variables')) {
                $this->addJsonColumn($table, 'retell_dynamic_variables', true);
            }
            
            if (!Schema::hasColumn('calls', 'opt_out_sensitive_data')) {
                $table->boolean('opt_out_sensitive_data')->default(false);
            }
            
            if (!Schema::hasColumn('calls', 'details')) {
                $this->addJsonColumn($table, 'details', true);
            }
            
            if (!Schema::hasColumn('calls', 'call_successful')) {
                $table->boolean('call_successful')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'conversation_id')) {
                $table->string('conversation_id')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'caller')) {
                $table->string('caller')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'duration')) {
                $table->integer('duration')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'duration_minutes')) {
                $table->decimal('duration_minutes', 8, 2)->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'phone_number')) {
                $table->string('phone_number')->nullable();
            }
            
            if (!Schema::hasColumn('calls', 'company_id')) {
                $table->bigInteger('company_id')->unsigned()->nullable();
            }
            
            // Add indexes for performance
            if (!Schema::hasIndex('calls', 'calls_recording_url_index')) {
                $table->index('recording_url');
            }
            
            if (!Schema::hasIndex('calls', 'calls_retell_agent_id_index')) {
                $table->index('retell_agent_id');
            }
            
            if (!Schema::hasIndex('calls', 'calls_status_index')) {
                $table->index('status');
            }
            
            if (!Schema::hasIndex('calls', 'calls_company_id_index')) {
                $table->index('company_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            return;
        }
        
        Schema::table('calls', function (Blueprint $table) {
            $columns = [
                'recording_url', 'call_status', 'retell_agent_id', 'transcription_id',
                'call_type', 'video_url', 'cost', 'appointment_id', 'staff_id',
                'analysis', 'raw_data', 'notes', 'metadata', 'tags', 'status',
                'start_timestamp', 'end_timestamp', 'direction', 'transcript_object',
                'transcript_with_tools', 'latency_metrics', 'cost_breakdown', 'llm_usage',
                'retell_dynamic_variables', 'opt_out_sensitive_data', 'details',
                'call_successful', 'conversation_id', 'caller', 'duration',
                'duration_minutes', 'phone_number', 'company_id'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('calls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};