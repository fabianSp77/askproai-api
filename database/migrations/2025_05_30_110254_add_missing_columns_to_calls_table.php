<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('calls', function (Blueprint $table) {
            // Nur hinzufügen, wenn die Spalten nicht existieren
            if (!Schema::hasColumn('calls', 'telefonnummer')) {
                $table->string('telefonnummer')->nullable()->after('email');
            }
            if (!Schema::hasColumn('calls', 'dienstleistung')) {
                $table->string('dienstleistung')->nullable()->after('telefonnummer');
            }
            if (!Schema::hasColumn('calls', 'grund')) {
                $table->text('grund')->nullable()->after('dienstleistung');
            }
            if (!Schema::hasColumn('calls', 'datum_termin')) {
                $table->date('datum_termin')->nullable()->after('grund');
            }
            if (!Schema::hasColumn('calls', 'uhrzeit_termin')) {
                $table->time('uhrzeit_termin')->nullable()->after('datum_termin');
            }
            if (!Schema::hasColumn('calls', 'calcom_booking_id')) {
                $table->string('calcom_booking_id')->nullable()->after('uhrzeit_termin');
            }
        });
    }

    public function down()
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn(['telefonnummer', 'dienstleistung', 'grund', 'datum_termin', 'uhrzeit_termin', 'calcom_booking_id']);
        });
    }
};
