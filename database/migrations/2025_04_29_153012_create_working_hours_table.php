<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();                                              // PK (BIGINT)
            $table->foreignId('staff_id')                              // FK → staff.id
                  ->constrained('staff')
                  ->cascadeOnDelete();

            $table->unsignedTinyInteger('weekday');                    // 1 = Mo … 7 = So
            $table->time('start');
            $table->time('end');

            $table->timestamps();

            // Ein Mitarbeiter darf pro Zeitfenster nur einen Eintrag haben
            $table->unique(['staff_id', 'weekday', 'start', 'end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_hours');
    }
};
