<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff_service')) {
            Schema::create('staff_service', function (Blueprint $table) {
                $table->id();                      // BIGINT PK
                $table->char('staff_id', 36);      // UUID
                $table->foreign('staff_id')
                      ->references('id')->on('staff')
                      ->cascadeOnDelete();

                $table->foreignId('service_id')    // BIGINT unsigned
                      ->constrained('services')
                      ->cascadeOnDelete();

                $table->timestamps();
                $table->unique(['staff_id', 'service_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_service');
    }
};

