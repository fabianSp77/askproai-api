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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('appointment_id')->index();
            $table->uuid('customer_id')->index();
            $table->uuid('company_id')->index();
            $table->string('type', 50); // confirmation, reminder, reschedule, cancellation
            $table->json('channels'); // ['email', 'sms', 'whatsapp']
            $table->json('successful_channels')->nullable();
            $table->json('failed_channels')->nullable();
            $table->json('errors')->nullable();
            $table->enum('status', ['sent', 'failed', 'partial'])->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            // Composite indexes
            $table->index(['company_id', 'created_at']);
            $table->index(['customer_id', 'type']);
            $table->index(['status', 'created_at']);
            
            // Foreign keys
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};