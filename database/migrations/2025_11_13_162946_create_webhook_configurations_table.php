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
        Schema::create('webhook_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name'); // Human-readable name
            $table->string('url'); // Webhook endpoint URL
            $table->json('subscribed_events'); // Array of events: ['callback.created', 'callback.assigned', etc.]
            $table->string('secret_key'); // For HMAC signature verification
            $table->boolean('is_active')->default(true);
            $table->integer('timeout_seconds')->default(10); // HTTP timeout
            $table->integer('max_retry_attempts')->default(3);
            $table->json('headers')->nullable(); // Custom headers to send
            $table->text('description')->nullable();
            $table->char('created_by', 36)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable(); // Staff ID (UUID) who created it
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('total_deliveries')->default(0);
            $table->integer('successful_deliveries')->default(0);
            $table->integer('failed_deliveries')->default(0);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('staff')->onDelete('set null');

            $table->index(['company_id', 'is_active']);
            $table->index('last_triggered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_configurations');
    }
};
