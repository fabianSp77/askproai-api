<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (Schema::hasTable('staff')) {
            Schema::table('staff', function (Blueprint $table) {
                if (!Schema::hasColumn('staff', 'max_daily_appointments')) {
                    $table->integer('max_daily_appointments')->default(8);
                }
            });
        }
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
        
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('max_daily_appointments');
        });
    }
};