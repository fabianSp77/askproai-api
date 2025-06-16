<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('staff', function (Blueprint $table) {
            // Füge nur die fehlenden Spalten hinzu
            if (!Schema::hasColumn('staff', 'calcom_calendar_link')) {
                $table->string('calcom_calendar_link')->nullable()->after('calcom_user_id');
            }
            if (!Schema::hasColumn('staff', 'is_bookable')) {
                $table->boolean('is_bookable')->default(true)->after('active');
            }
        });
        
        // Ändere calendar_mode zu ENUM wenn es existiert
        if (Schema::hasColumn('staff', 'calendar_mode')) {
            DB::statement("ALTER TABLE staff MODIFY calendar_mode ENUM('inherit', 'own', 'shared') DEFAULT 'inherit'");
        }
    }

    public function down()
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['calcom_calendar_link', 'is_bookable']);
        });
        
        // Ändere zurück zu varchar
        if (Schema::hasColumn('staff', 'calendar_mode')) {
            DB::statement("ALTER TABLE staff MODIFY calendar_mode VARCHAR(255) DEFAULT 'inherit'");
        }
    }
};
