<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_service', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // ----- Beziehungen -----
            $table->uuid('staff_id');
            $table->foreign('staff_id')
                  ->references('id')->on('staff')
                  ->onDelete('cascade');

            $table->uuid('service_id');
            $table->foreign('service_id')
                  ->references('id')->on('services')
                  ->onDelete('cascade');

            // ----- Metadaten -----
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_service');
    }
};
