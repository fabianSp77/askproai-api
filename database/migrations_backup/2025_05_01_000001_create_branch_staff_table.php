<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_staff', function (Blueprint $table) {
            // kein eigenes id-Feld – Kombi-PK reicht
            $table->foreignId('branch_id')
                  ->constrained('branches')
                  ->cascadeOnDelete();

            $table->foreignId('staff_id')
                  ->constrained('staff')
                  ->cascadeOnDelete();

            $table->primary(['branch_id', 'staff_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_staff');
    }
};
