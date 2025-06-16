<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_service_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id');
            $table->uuid('master_service_id');
            $table->integer('custom_duration')->nullable();
            $table->decimal('custom_price', 10, 2)->nullable();
            $table->string('custom_calcom_event_type_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('master_service_id')->references('id')->on('master_services')->onDelete('cascade');
            $table->unique(['branch_id', 'master_service_id']);
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_service_overrides');
    }
};
