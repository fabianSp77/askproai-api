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
        Schema::create('booking_flow_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('correlation_id')->index();
            $table->string('step', 100);
            $table->uuid('company_id')->nullable()->index();
            $table->uuid('branch_id')->nullable()->index();
            $table->uuid('customer_id')->nullable()->index();
            $table->uuid('appointment_id')->nullable()->index();
            $table->json('context');
            $table->timestamps();
            
            // Composite indexes for common queries
            $table->index(['company_id', 'created_at']);
            $table->index(['appointment_id', 'step']);
            
            // Foreign keys (assuming UUIDs for all models)
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_flow_logs');
    }
};