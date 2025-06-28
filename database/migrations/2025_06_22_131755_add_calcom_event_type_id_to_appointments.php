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
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'calcom_event_type_id')) {
                $table->bigInteger('calcom_event_type_id')->unsigned()->nullable()->after('service_id');
                $table->index('calcom_event_type_id');
                
                // Add foreign key to calcom_event_types
                $table->foreign('calcom_event_type_id')
                      ->references('calcom_numeric_event_type_id')
                      ->on('calcom_event_types')
                      ->onDelete('set null');
            }
        });
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
            $table->dropForeign(['calcom_event_type_id']);
            $table->dropColumn('calcom_event_type_id');
        });
    }
};