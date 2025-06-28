<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'calcom_event_type_id')) {
                $table->string('calcom_event_type_id')->nullable();
            }
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
        
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('calcom_event_type_id');
        });
    }
};
