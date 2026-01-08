<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates notification_deliveries table for tracking individual delivery
     * attempts across multiple channels (SMS, Email, Push, Webhook, etc.)
     */
    public function up(): void
    {
        // Skip if notification_queue doesn't exist (FK dependency)
        if (!Schema::hasTable('notification_queue')) {
            return;
        }

        if (!Schema::hasTable('notification_deliveries')) {
            Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();

            // Foreign key to notification_queue
            $table->foreignId('notification_queue_id')
                  ->constrained('notification_queue')
                  ->cascadeOnDelete();

            // Channel and status
            $table->string('channel', 50);  // sms, email, push, webhook, etc.
            $table->string('status', 50)->default('pending');  // pending, sending, sent, failed, delivered, bounced

            // Provider tracking
            $table->string('provider_name', 50)->nullable();  // Twilio, SendGrid, Firebase, etc.
            $table->string('provider_message_id', 255)->nullable();  // Provider's message ID for tracking
            $table->json('provider_response')->nullable();  // Full response from provider

            // Error tracking
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);

            // Timestamps for delivery lifecycle
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index('notification_queue_id');
            $table->index('channel');
            $table->index('status');
            $table->index('created_at');
            $table->index(['status', 'created_at']);
            $table->index(['notification_queue_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
