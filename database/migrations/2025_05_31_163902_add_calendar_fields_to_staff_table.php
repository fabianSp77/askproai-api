<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        Schema::table('staff', function (Blueprint $table) {
            // Füge nur die fehlenden Spalten hinzu
            if (!Schema::hasColumn('staff', 'calcom_calendar_link')) {
                $table->string('calcom_calendar_link')->nullable();
            }
            if (!Schema::hasColumn('staff', 'is_bookable')) {
                $table->boolean('is_bookable')->default(true);
            }
        });
        
        // Ändere calendar_mode zu ENUM wenn es existiert
        if (Schema::hasColumn('staff', 'calendar_mode')) {
            DB::statement("ALTER TABLE staff MODIFY calendar_mode ENUM('inherit', 'own', 'shared') DEFAULT 'inherit'");
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
        
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['calcom_calendar_link', 'is_bookable']);
        });
        
        // Ändere zurück zu varchar
        if (Schema::hasColumn('staff', 'calendar_mode')) {
            DB::statement("ALTER TABLE staff MODIFY calendar_mode VARCHAR(255) DEFAULT 'inherit'");
        }
    }
};
