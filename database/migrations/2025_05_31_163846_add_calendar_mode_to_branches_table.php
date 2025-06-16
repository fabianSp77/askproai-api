<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            // Prüfe und ändere calendar_mode falls nötig
            if (Schema::hasColumn('branches', 'calendar_mode')) {
                // Ändere den Typ zu ENUM wenn es noch varchar ist
                DB::statement("ALTER TABLE branches MODIFY calendar_mode ENUM('inherit', 'override') DEFAULT 'inherit'");
            } else {
                $table->enum('calendar_mode', ['inherit', 'override'])->default('inherit');
            }
            
            // Die anderen Felder existieren bereits, also nichts zu tun
        });
    }

    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            // Ändere zurück zu varchar für Rollback
            DB::statement("ALTER TABLE branches MODIFY calendar_mode VARCHAR(255) DEFAULT 'inherit'");
        });
    }
};
