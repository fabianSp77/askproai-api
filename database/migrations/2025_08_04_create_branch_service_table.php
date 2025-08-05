<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('branch_service', function (Blueprint $table) {
            $table->id();
            $table->char('branch_id', 36);
            $table->unsignedBigInteger('service_id');
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('duration')->nullable()->comment('Duration in minutes');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            
            $table->unique(['branch_id', 'service_id']);
            $table->index('branch_id');
            $table->index('service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_service');
    }
};