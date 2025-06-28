<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('calcom_event_type_id')->nullable();
            $table->index('calcom_event_type_id');
        });
    }

    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['calcom_event_type_id']);
            $table->dropColumn('calcom_event_type_id');
        });
    }
};
