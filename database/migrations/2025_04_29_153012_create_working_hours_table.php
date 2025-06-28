<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Arbeitszeiten-Tabelle: gehört zu einem Staff-Mitglied (UUID)
     */
    public function up(): void
    {
        $this->createTableIfNotExists('working_hours', function (Blueprint $table) {
            $table->id();                   // bigint auto-inc (PK)

            // FK-Spalten -----------------
            $table->char('staff_id', 36);   // UUID – muss zu staff.id passen
            $table->tinyInteger('weekday'); // 0=So … 6=Sa
            $table->time('start');
            $table->time('end');

            // FK-Constraint --------------
            $table->foreign('staff_id')
                  ->references('id')->on('staff')
                  ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->dropTableIfExists('working_hours');
    }
};
