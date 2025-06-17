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
            $table->char('company_id', 36)->nullable()->index();
            $table->char('branch_id', 36)->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->char('appointment_id', 36)->nullable()->index();
            $table->json('context')->nullable();
            $table->timestamps();
            
            // Composite indexes for common queries
            $table->index(['company_id', 'created_at']);
            $table->index(['correlation_id', 'created_at']);
            $table->index(['step', 'created_at']);
            
            // Foreign keys (optional - depends on your referential integrity needs)
            // $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            // $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            // $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            // $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('set null');
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