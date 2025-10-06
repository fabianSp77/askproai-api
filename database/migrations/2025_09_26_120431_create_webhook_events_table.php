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
        if (Schema::hasTable('webhook_events')) {
            return;
        }

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('source'); // 'calcom', 'retell', 'stripe'
            $table->string('event_type')->nullable(); // 'booking.created', 'call.ended', etc
            $table->string('event_id')->nullable(); // External event ID
            $table->string('url'); // Webhook URL that received the event
            $table->string('method')->default('POST');
            $table->json('headers')->nullable();
            $table->json('payload');
            $table->json('response')->nullable();
            $table->integer('response_code')->nullable();
            $table->enum('status', ['pending', 'processing', 'processed', 'failed', 'ignored'])->default('pending');
            $table->string('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->string('related_model_type')->nullable(); // 'App\Models\Call', 'App\Models\Appointment'
            $table->unsignedBigInteger('related_model_id')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('source');
            $table->index('event_type');
            $table->index('status');
            $table->index(['source', 'status']);
            $table->index(['related_model_type', 'related_model_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
