<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('working_hours');
    }
};
