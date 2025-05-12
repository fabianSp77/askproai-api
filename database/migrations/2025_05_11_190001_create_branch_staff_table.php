<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_staff', function (Blueprint $table) {
            // UUID ↔ BIGINT – beide Typen exakt wie in den Referenztabellen!
            $table->uuid('branch_id');
            $table->unsignedBigInteger('staff_id');
            $table->timestamps();

            $table->primary(['branch_id', 'staff_id']);

            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_staff');
    }
};
