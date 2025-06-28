<?php

namespace App\Traits;

use App\Jobs\ProcessAlertJob;
use App\Services\Monitoring\UnifiedAlertingService;
use Illuminate\Support\Facades\Log;

trait TracksWebhookFailures
{
    /**
     * Handle webhook processing failure.
     */
    protected function handleWebhookFailure(
        string $provider,
        string $eventType,
        \Exception $exception,
        ?array $payload = null
    ): void {
        // Log the failure
        Log::error("Webhook processing failed for {$provider}", [
            'provider' => $provider,
            'event_type' => $eventType,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'payload' => $payload,
        ]);

        // Record event for alerting
        if ($provider === 'stripe') {
            app(UnifiedAlertingService::class)->recordEvent('stripe_webhook_failure');
        }

        // Dispatch alert job
        ProcessAlertJob::dispatch("{$provider}_webhook_failure", [
            'provider' => $provider,
            'event_type' => $eventType,
            'error_message' => $exception->getMessage(),
        ])->onQueue('alerts');
    }

    /**
     * Track webhook success for metrics.
     */
    protected function trackWebhookSuccess(string $provider, string $eventType): void
    {
        Log::info('Webhook processed successfully', [
            'provider' => $provider,
            'event_type' => $eventType,
        ]);

        // Could add metrics tracking here
    }
}
