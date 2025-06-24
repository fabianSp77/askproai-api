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
        Schema::create('callback_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->char('branch_id', 36)->nullable();
            $table->unsignedBigInteger('call_id');
            $table->string('customer_phone', 50);
            $table->string('customer_name')->nullable();
            $table->text('requested_service')->nullable();
            $table->date('requested_date')->nullable();
            $table->time('requested_time')->nullable();
            $table->enum('reason', ['calcom_error', 'no_availability', 'technical_error', 'customer_request']);
            $table->json('error_details')->nullable();
            $table->text('call_summary')->nullable();
            $table->enum('priority', ['urgent', 'high', 'normal', 'low'])->default('normal');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'auto_closed', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->text('completion_notes')->nullable();
            $table->integer('auto_close_after_hours')->default(24);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('auto_closed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'company_id', 'created_at'], 'idx_status_company');
            $table->index('customer_phone', 'idx_phone');
            $table->index(['priority', 'status'], 'idx_priority');
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('callback_requests');
    }
};
