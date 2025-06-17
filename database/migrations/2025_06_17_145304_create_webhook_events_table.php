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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // retell, calcom, stripe, etc.
            $table->string('event_type'); // call_ended, booking.created, etc.
            $table->string('event_id')->unique(); // unique ID from provider
            $table->string('idempotency_key')->unique(); // computed hash for deduplication
            $table->json('payload'); // full webhook payload
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->string('correlation_id')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['provider', 'event_type']);
            $table->index('status');
            $table->index('created_at');
            $table->index('correlation_id');
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
