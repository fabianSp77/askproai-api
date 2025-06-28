<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        Schema::table('calls', function (Blueprint $table) {
            // Nur hinzufügen, wenn die Spalten nicht existieren
            if (!Schema::hasColumn('calls', 'telefonnummer')) {
                $table->string('telefonnummer')->nullable();
            }
            if (!Schema::hasColumn('calls', 'dienstleistung')) {
                $table->string('dienstleistung')->nullable();
            }
            if (!Schema::hasColumn('calls', 'grund')) {
                $table->text('grund')->nullable();
            }
            if (!Schema::hasColumn('calls', 'datum_termin')) {
                $table->date('datum_termin')->nullable();
            }
            if (!Schema::hasColumn('calls', 'uhrzeit_termin')) {
                $table->time('uhrzeit_termin')->nullable();
            }
            if (!Schema::hasColumn('calls', 'calcom_booking_id')) {
                $table->string('calcom_booking_id')->nullable();
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
        
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['telefonnummer', 'dienstleistung', 'grund', 'datum_termin', 'uhrzeit_termin', 'calcom_booking_id']);
        });
    }
};
