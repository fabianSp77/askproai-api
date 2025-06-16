<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // Füge fehlende Spalten hinzu, falls sie noch nicht existieren
            if (!Schema::hasColumn('staff', 'calcom_username')) {
                $table->string('calcom_username')->nullable()->after('calcom_user_id');
            }
            
            if (!Schema::hasColumn('staff', 'working_hours')) {
                $table->json('working_hours')->nullable()->after('calcom_username');
            }
            
            // is_bookable existiert bereits laut Model
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $columns = ['calcom_username', 'working_hours'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('staff', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};