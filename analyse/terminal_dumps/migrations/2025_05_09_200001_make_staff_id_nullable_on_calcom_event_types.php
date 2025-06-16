<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * staff_id in calcom_event_types auf NULL-fähig umstellen.
     * Läuft nur, wenn Tabelle & Spalte bereits vorhanden sind.
     */
    public function up(): void
    {
        if (Schema::hasTable('calcom_event_types')
            && Schema::hasColumn('calcom_event_types', 'staff_id')) {

            Schema::table('calcom_event_types', function (Blueprint $table) {
                $table->char('staff_id', 36)->nullable()->change();   // NULL jetzt erlaubt
            });
        }
    }

    /**
     * Rollback: staff_id wieder NOT NULL.
     * Ebenfalls nur, wenn Tabelle & Spalte existieren.
     */
    public function down(): void
    {
        if (Schema::hasTable('calcom_event_types')
            && Schema::hasColumn('calcom_event_types', 'staff_id')) {

            Schema::table('calcom_event_types', function (Blueprint $table) {
                $table->char('staff_id', 36)->nullable(false)->change(); // NULL-Verbot wiederherstellen
            });
        }
    }
};
