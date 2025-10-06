<?php

namespace App\Traits;

use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait LogsWebhookEvents
{
    /**
     * Log an incoming webhook event
     */
    protected function logWebhookEvent(Request $request, string $source, array $payload = null): WebhookEvent
    {
        // Get payload if not provided
        if ($payload === null) {
            $payload = $request->all();
        }

        // Extract event type based on source
        $eventType = $this->extractEventType($source, $payload);

        // Extract event ID based on source
        $eventId = $this->extractEventId($source, $payload);

        // Create webhook event record (using existing table structure)
        $webhookEvent = WebhookEvent::create([
            'provider' => $source, // Using 'provider' instead of 'source'
            'event_type' => $eventType,
            'event_id' => $eventId,
            'idempotency_key' => $eventId ?? uniqid($source . '_', true), // Generate unique key
            'headers' => $request->headers->all(),
            'payload' => $payload,
            'status' => 'pending',
            'received_at' => now(),
            'company_id' => null, // Will be set based on context
            'retry_count' => 0,
        ]);

        Log::info("🔔 Webhook Event Logged", [
            'id' => $webhookEvent->id,
            'provider' => $source,
            'event_type' => $eventType,
            'event_id' => $eventId,
        ]);

        return $webhookEvent;
    }

    /**
     * Extract event type based on source
     */
    protected function extractEventType(string $source, array $payload): ?string
    {
        switch ($source) {
            case 'calcom':
                return $payload['triggerEvent'] ?? null;

            case 'retell':
                // Check for intent-based events
                if (isset($payload['payload']['intent'])) {
                    return 'intent.' . $payload['payload']['intent'];
                }
                // Check for call events
                if (isset($payload['event'])) {
                    return $payload['event'];
                }
                return null;

            case 'stripe':
                return $payload['type'] ?? null;

            default:
                return null;
        }
    }

    /**
     * Extract event ID based on source
     */
    protected function extractEventId(string $source, array $payload): ?string
    {
        switch ($source) {
            case 'calcom':
                return $payload['payload']['uid'] ??
                       $payload['payload']['id'] ??
                       null;

            case 'retell':
                return $payload['call_id'] ??
                       $payload['data']['call_id'] ??
                       $payload['call']['call_id'] ??
                       null;

            case 'stripe':
                return $payload['id'] ?? null;

            default:
                return null;
        }
    }

    /**
     * Mark webhook event as processed
     */
    protected function markWebhookProcessed(WebhookEvent $webhookEvent, $relatedModel = null, $response = null): void
    {
        $notes = null;
        if ($relatedModel) {
            $notes = get_class($relatedModel) . ':' . $relatedModel->id;
        }
        if ($response !== null) {
            $notes = ($notes ? $notes . ' | ' : '') . json_encode(is_array($response) ? $response : ['message' => $response]);
        }

        $webhookEvent->markAsProcessed($notes);

        Log::info("✅ Webhook Event Processed", [
            'id' => $webhookEvent->id,
            'provider' => $webhookEvent->provider,
            'event_type' => $webhookEvent->event_type,
            'notes' => $notes,
        ]);
    }

    /**
     * Mark webhook event as failed
     */
    protected function markWebhookFailed(WebhookEvent $webhookEvent, string $error, $response = null, int $responseCode = 500): void
    {
        $webhookEvent->markAsFailed($error);

        if ($response !== null) {
            $notes = json_encode(is_array($response) ? $response : ['error' => $response]);
            $webhookEvent->update(['notes' => $notes]);
        }

        Log::error("❌ Webhook Event Failed", [
            'id' => $webhookEvent->id,
            'provider' => $webhookEvent->provider,
            'event_type' => $webhookEvent->event_type,
            'error' => $error,
            'retry_count' => $webhookEvent->retry_count,
        ]);
    }

    /**
     * Mark webhook event as ignored
     */
    protected function markWebhookIgnored(WebhookEvent $webhookEvent, string $reason = null): void
    {
        $webhookEvent->update([
            'status' => 'ignored',
            'processed_at' => now(),
            'notes' => $reason ?? 'Event type not handled',
        ]);

        Log::info("⏭️ Webhook Event Ignored", [
            'id' => $webhookEvent->id,
            'provider' => $webhookEvent->provider,
            'event_type' => $webhookEvent->event_type,
            'reason' => $reason,
        ]);
    }
}