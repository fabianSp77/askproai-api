<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_service', function (Blueprint $table) {
            $table->foreignId('branch_id')
                  ->constrained('branches')
                  ->cascadeOnDelete();

            $table->foreignId('service_id')
                  ->constrained('services')
                  ->cascadeOnDelete();

            $table->primary(['branch_id', 'service_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_service');
    }
};
