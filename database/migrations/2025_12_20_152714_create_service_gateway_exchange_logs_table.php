<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Service Gateway Exchange Logs - Audit trail for all external communications.
     * Stores redacted request/response data for forensics and debugging.
     *
     * Key features:
     * - No-Leak Guarantee: All sensitive data is redacted before storage
     * - Correlation tracking: Links logs to calls, tickets, and companies
     * - Retry tracking: Tracks delivery attempts with exponential backoff
     * - Performance metrics: Duration and timing for SLA monitoring
     */
    public function up(): void
    {
        Schema::create('service_gateway_exchange_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique()->comment('Unique event identifier for correlation');

            // Direction of communication
            $table->enum('direction', ['outbound', 'inbound'])->default('outbound')
                ->comment('outbound=we send to external, inbound=external sends to us');

            // Correlation IDs
            $table->foreignId('call_id')->nullable()->constrained('calls')->nullOnDelete();
            $table->foreignId('service_case_id')->nullable()->comment('Links to service_cases.id');
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();

            // Endpoint information
            $table->string('endpoint', 500)->comment('Target URL (domain only for outbound)');
            $table->string('http_method', 10)->default('POST');

            // Payload data (REDACTED - no sensitive information)
            $table->json('request_body_redacted')->nullable()
                ->comment('Request payload with sensitive data redacted');
            $table->json('response_body_redacted')->nullable()
                ->comment('Response payload with sensitive data redacted');
            $table->json('headers_redacted')->nullable()
                ->comment('HTTP headers with auth tokens redacted');

            // Response tracking
            $table->unsignedSmallInteger('status_code')->nullable()
                ->comment('HTTP status code (200, 400, 500, etc.)');
            $table->unsignedInteger('duration_ms')->nullable()
                ->comment('Request duration in milliseconds');

            // Retry tracking
            $table->unsignedTinyInteger('attempt_no')->default(1)
                ->comment('Delivery attempt number (1=first try)');
            $table->unsignedTinyInteger('max_attempts')->default(3)
                ->comment('Maximum retry attempts configured');

            // Error tracking
            $table->string('error_class', 255)->nullable()
                ->comment('Exception class name if failed');
            $table->text('error_message')->nullable()
                ->comment('Error message (sanitized)');

            // Correlation for distributed tracing
            $table->uuid('correlation_id')->nullable()
                ->comment('External correlation/trace ID');
            $table->uuid('parent_event_id')->nullable()
                ->comment('Parent event for retry chains');

            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('completed_at')->nullable()
                ->comment('When the exchange completed (success or final failure)');

            // Indexes for efficient querying
            $table->index(['company_id', 'created_at'], 'idx_company_timeline');
            $table->index(['service_case_id'], 'idx_case_logs');
            $table->index(['call_id'], 'idx_call_logs');
            $table->index(['correlation_id'], 'idx_correlation');
            $table->index(['status_code', 'created_at'], 'idx_status_monitoring');
            $table->index(['direction', 'created_at'], 'idx_direction_timeline');
            $table->index(['error_class'], 'idx_error_analysis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_gateway_exchange_logs');
    }
};
