<?php

namespace App\Services\Webhooks;

use App\Jobs\DeliverWebhookJob;
use App\Models\CallbackRequest;
use App\Models\WebhookConfiguration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CallbackWebhookService
 *
 * Orchestrates outgoing webhooks for CallbackRequest events.
 * Finds subscribed webhooks, prepares payloads, and dispatches delivery jobs.
 *
 * Usage:
 *   CallbackWebhookService::dispatch('callback.created', $callbackRequest);
 */
class CallbackWebhookService
{
    /**
     * Dispatch webhooks for a callback event.
     *
     * @param string $event Event type (e.g., 'callback.created')
     * @param CallbackRequest $callbackRequest
     * @param array $additionalData Optional additional data to include in payload
     * @return int Number of webhooks dispatched
     */
    public static function dispatch(string $event, CallbackRequest $callbackRequest, array $additionalData = []): int
    {
        // Find active webhooks subscribed to this event for this company
        $webhooks = WebhookConfiguration::where('company_id', $callbackRequest->company_id)
            ->active()
            ->subscribedTo($event)
            ->get();

        if ($webhooks->isEmpty()) {
            Log::debug('[Webhook] No active webhooks found for event', [
                'event' => $event,
                'company_id' => $callbackRequest->company_id,
                'callback_id' => $callbackRequest->id,
            ]);
            return 0;
        }

        // Prepare payload
        $payload = self::preparePayload($callbackRequest, $additionalData);

        // Generate idempotency key for this event
        $idempotencyKey = self::generateIdempotencyKey($event, $callbackRequest);

        // Dispatch delivery job for each webhook
        $dispatched = 0;
        foreach ($webhooks as $webhook) {
            try {
                DeliverWebhookJob::dispatch($webhook, $event, $payload, $idempotencyKey);
                $dispatched++;

                Log::info('[Webhook] Job dispatched', [
                    'webhook_id' => $webhook->id,
                    'webhook_name' => $webhook->name,
                    'event' => $event,
                    'callback_id' => $callbackRequest->id,
                ]);
            } catch (\Exception $e) {
                Log::error('[Webhook] Failed to dispatch job', [
                    'webhook_id' => $webhook->id,
                    'event' => $event,
                    'callback_id' => $callbackRequest->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[Webhook] Dispatched webhooks', [
            'event' => $event,
            'callback_id' => $callbackRequest->id,
            'dispatched_count' => $dispatched,
            'total_webhooks' => $webhooks->count(),
        ]);

        return $dispatched;
    }

    /**
     * Prepare webhook payload from CallbackRequest.
     *
     * @param CallbackRequest $callbackRequest
     * @param array $additionalData
     * @return array
     */
    protected static function preparePayload(CallbackRequest $callbackRequest, array $additionalData = []): array
    {
        // Load relationships for complete payload
        $callbackRequest->load(['customer', 'branch', 'service', 'staff', 'assignedTo']);

        $payload = [
            'callback_request' => [
                'id' => $callbackRequest->id,
                'customer_id' => $callbackRequest->customer_id,
                'customer_name' => $callbackRequest->customer_name,
                'phone_number' => $callbackRequest->phone_number,
                'branch_id' => $callbackRequest->branch_id,
                'branch_name' => $callbackRequest->branch?->name,
                'service_id' => $callbackRequest->service_id,
                'service_name' => $callbackRequest->service?->name,
                'staff_id' => $callbackRequest->staff_id,
                'staff_name' => $callbackRequest->staff?->name,
                'assigned_to' => $callbackRequest->assigned_to,
                'assigned_to_name' => $callbackRequest->assignedTo?->name,
                'preferred_time_window' => $callbackRequest->preferred_time_window,
                'priority' => $callbackRequest->priority,
                'status' => $callbackRequest->status,
                'notes' => $callbackRequest->notes,
                'metadata' => $callbackRequest->metadata,
                'assigned_at' => $callbackRequest->assigned_at?->toIso8601String(),
                'contacted_at' => $callbackRequest->contacted_at?->toIso8601String(),
                'completed_at' => $callbackRequest->completed_at?->toIso8601String(),
                'expires_at' => $callbackRequest->expires_at?->toIso8601String(),
                'created_at' => $callbackRequest->created_at->toIso8601String(),
                'updated_at' => $callbackRequest->updated_at->toIso8601String(),
                'is_overdue' => $callbackRequest->is_overdue,
            ],
        ];

        // Merge additional data
        if (!empty($additionalData)) {
            $payload = array_merge($payload, $additionalData);
        }

        return $payload;
    }

    /**
     * Generate idempotency key for this specific event + callback.
     *
     * @param string $event
     * @param CallbackRequest $callbackRequest
     * @return string
     */
    protected static function generateIdempotencyKey(string $event, CallbackRequest $callbackRequest): string
    {
        // Format: callback_{id}_{event}_{updated_at_timestamp}
        // This ensures same event at different times gets different keys
        return sprintf(
            'callback_%d_%s_%d',
            $callbackRequest->id,
            str_replace('.', '_', $event),
            $callbackRequest->updated_at->timestamp
        );
    }

    /**
     * Test webhook delivery for a specific webhook configuration.
     *
     * @param WebhookConfiguration $webhook
     * @return bool Success status
     */
    public static function testWebhook(WebhookConfiguration $webhook): bool
    {
        $testPayload = [
            'test' => true,
            'message' => 'This is a test webhook delivery from AskPro AI Gateway',
            'webhook_name' => $webhook->name,
            'timestamp' => now()->toIso8601String(),
        ];

        $idempotencyKey = 'test_' . Str::random(16);

        try {
            DeliverWebhookJob::dispatch($webhook, 'callback.test', $testPayload, $idempotencyKey);

            Log::info('[Webhook] Test webhook dispatched', [
                'webhook_id' => $webhook->id,
                'webhook_name' => $webhook->name,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('[Webhook] Test webhook failed', [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
