<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        if (!Schema::hasColumn('calls', 'calcom_booking_id')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->string('calcom_booking_id')->nullable();
            });
        }
    }

    public function down()
    {
        // SQLite can't drop columns with indexes present
        if ($this->isSQLite()) {
            // For SQLite, we just skip the drop
            // The columns will remain but won't cause issues
            return;
        }
        
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn('calcom_booking_id');
        });
    }
};
