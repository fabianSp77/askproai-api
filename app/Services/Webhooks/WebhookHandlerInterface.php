<?php

namespace App\Services\Webhooks;

use App\Models\WebhookEvent;

interface WebhookHandlerInterface
{
    /**
     * Handle the webhook event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    public function handle(WebhookEvent $webhookEvent, string $correlationId): array;
    
    /**
     * Get supported event types for this handler
     *
     * @return array
     */
    public function getSupportedEvents(): array;
    
    /**
     * Check if the handler supports a specific event type
     *
     * @param string $eventType
     * @return bool
     */
    public function supportsEvent(string $eventType): bool;
}