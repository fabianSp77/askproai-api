<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiVersionToCalcomBookings extends CompatibleMigration
{
    public function up()
    {
        Schema::table('calcom_bookings', function (Blueprint $table) {
            $table->enum('api_version', ['v1', 'v2'])->default('v1');
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
        
        Schema::table('calcom_bookings', function (Blueprint $table) {
            $table->dropColumn('api_version');
        });
    }
}
