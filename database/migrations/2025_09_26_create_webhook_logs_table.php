<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('webhook_logs')) {
            return;
        }

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50)->index(); // retell, calcom, stripe
            $table->string('endpoint');
            $table->string('method', 10);
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->enum('status', ['received', 'processing', 'processed', 'failed', 'error'])->default('received');
            $table->text('error_message')->nullable();
            $table->integer('response_code')->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->string('event_type', 100)->nullable()->index(); // BOOKING_CREATED, call_ended, etc.
            $table->string('event_id', 255)->nullable()->index(); // External ID from webhook
            $table->timestamps();

            // Indexes for performance
            $table->index(['source', 'status']);
            $table->index(['source', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};