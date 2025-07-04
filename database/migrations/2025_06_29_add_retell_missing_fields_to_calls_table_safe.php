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
            // Check and add missing fields only if they don't exist
            
            if (!Schema::hasColumn('calls', 'session_outcome')) {
                $table->string('session_outcome', 50)->nullable()->after('call_status');
            }
            
            if (!Schema::hasColumn('calls', 'health_insurance_company')) {
                $table->string('health_insurance_company')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('calls', 'appointment_made')) {
                $table->boolean('appointment_made')->default(false)->after('appointment_id');
            }
            
            if (!Schema::hasColumn('calls', 'reason_for_visit')) {
                $table->text('reason_for_visit')->nullable()->after('dienstleistung');
            }
            
            if (!Schema::hasColumn('calls', 'end_to_end_latency')) {
                $table->integer('end_to_end_latency')->nullable()->after('latency_metrics');
            }
            
            if (!Schema::hasColumn('calls', 'duration_ms')) {
                $table->integer('duration_ms')->nullable()->after('duration_sec')->comment('Duration in milliseconds from Retell');
            }
        });
        
        // Modify agent_version from int to string if needed
        if (Schema::hasColumn('calls', 'agent_version')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->string('agent_version', 50)->nullable()->change();
            });
        }
        
        // Skip indexes due to table limit
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            
            // Drop columns if they exist
            $columnsToDrop = [];
            
            if (Schema::hasColumn('calls', 'session_outcome')) {
                $columnsToDrop[] = 'session_outcome';
            }
            if (Schema::hasColumn('calls', 'health_insurance_company')) {
                $columnsToDrop[] = 'health_insurance_company';
            }
            if (Schema::hasColumn('calls', 'appointment_made')) {
                $columnsToDrop[] = 'appointment_made';
            }
            if (Schema::hasColumn('calls', 'reason_for_visit')) {
                $columnsToDrop[] = 'reason_for_visit';
            }
            if (Schema::hasColumn('calls', 'end_to_end_latency')) {
                $columnsToDrop[] = 'end_to_end_latency';
            }
            if (Schema::hasColumn('calls', 'duration_ms')) {
                $columnsToDrop[] = 'duration_ms';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};