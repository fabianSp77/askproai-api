<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCalcomV2Fields extends CompatibleMigration
{
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('calcom_v2_booking_id')->nullable();
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
        
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('calcom_v2_booking_id');
        });
    }
}
