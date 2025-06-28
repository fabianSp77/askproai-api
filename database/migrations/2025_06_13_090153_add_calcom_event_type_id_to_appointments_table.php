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
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'calcom_event_type_id')) {
                $table->unsignedBigInteger('calcom_event_type_id')
                    ->nullable()
                    ;
                
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
    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'calcom_event_type_id')) {
                $table->dropForeign(['calcom_event_type_id']);
                $table->dropIndex(['calcom_event_type_id']);
                $table->dropColumn('calcom_event_type_id');
            }
        });
    }
};
