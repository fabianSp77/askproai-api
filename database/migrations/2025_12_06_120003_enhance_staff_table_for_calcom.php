<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // Füge fehlende Spalten hinzu, falls sie noch nicht existieren
            if (!Schema::hasColumn('staff', 'calcom_username')) {
                $table->string('calcom_username')->nullable();
            }
            
            if (!Schema::hasColumn('staff', 'working_hours')) {
                $this->addJsonColumn($table, 'working_hours', true);
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