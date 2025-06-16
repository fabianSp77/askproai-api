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
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'calcom_event_type_id')) {
                $table->unsignedBigInteger('calcom_event_type_id')
                    ->nullable()
                    ->after('company_id');
                
                $table->index('calcom_event_type_id');
                
                // Add foreign key constraint
                $table->foreign('calcom_event_type_id')
                    ->references('id')
                    ->on('calcom_event_types')
                    ->onDelete('set null');
            }
        });
        
        // Note: Since appointments don't have a direct service_id,
        // the calcom_event_type_id will need to be populated through
        // other means (e.g., when creating appointments from calcom bookings)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'calcom_event_type_id')) {
                $table->dropForeign(['calcom_event_type_id']);
                $table->dropIndex(['calcom_event_type_id']);
                $table->dropColumn('calcom_event_type_id');
            }
        });
    }
};
