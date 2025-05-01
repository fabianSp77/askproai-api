<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('staff_id');
            $table->foreign('staff_id')
                  ->references('id')->on('staff')
                  ->onDelete('cascade');

            // Beispiel-Felder (anpassen, falls nötig)
            $table->unsignedTinyInteger('weekday');   // 0 = So … 6 = Sa
            $table->time('start');
            $table->time('end');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_hours');
    }
};
