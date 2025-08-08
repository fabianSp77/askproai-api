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
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('calls', 'lead_status')) {
                $table->enum('lead_status', [
                    'contacted',
                    'no_answer',
                    'busy',
                    'failed',
                    'connected',
                    'qualified',
                    'not_interested',
                    'appointment_set',
                    'follow_up_required'
                ])->nullable()->after('status')->comment('Lead qualification status for outbound calls');
            }
            
            if (!Schema::hasColumn('calls', 'data_validation_completed')) {
                $table->boolean('data_validation_completed')->default(false)
                    ->after('data_forwarded')
                    ->comment('Whether customer data has been validated');
            }
            
            if (!Schema::hasColumn('calls', 'appointment_with_advisor_id')) {
                $table->unsignedBigInteger('appointment_with_advisor_id')->nullable()
                    ->after('appointment_id')
                    ->comment('ID of advisor appointment for insurance consultations');
            }
        });
        
        // Add indexes separately (only if they don't exist)
        Schema::table('calls', function (Blueprint $table) {
            $connection = Schema::getConnection();
            $indexes = $connection->getDoctrineSchemaManager()->listTableIndexes('calls');
            
            if (!isset($indexes['calls_lead_status_index'])) {
                $table->index('lead_status');
            }
            
            if (!isset($indexes['calls_direction_lead_status_index'])) {
                $table->index(['direction', 'lead_status']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $connection = Schema::getConnection();
            $indexes = $connection->getDoctrineSchemaManager()->listTableIndexes('calls');
            
            if (isset($indexes['calls_lead_status_index'])) {
                $table->dropIndex('calls_lead_status_index');
            }
            
            if (isset($indexes['calls_direction_lead_status_index'])) {
                $table->dropIndex('calls_direction_lead_status_index');
            }
            
            if (Schema::hasColumn('calls', 'lead_status')) {
                $table->dropColumn('lead_status');
            }
            
            if (Schema::hasColumn('calls', 'data_validation_completed')) {
                $table->dropColumn('data_validation_completed');
            }
            
            if (Schema::hasColumn('calls', 'appointment_with_advisor_id')) {
                $table->dropColumn('appointment_with_advisor_id');
            }
        });
    }
};