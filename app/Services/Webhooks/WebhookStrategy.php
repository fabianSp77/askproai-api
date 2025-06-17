<?php

namespace App\Services\Webhooks;

use Illuminate\Http\Request;

interface WebhookStrategy
{
    /**
     * Validate the webhook signature
     */
    public function validateSignature(Request $request): bool;
    
    /**
     * Process the webhook payload
     */
    public function process(array $payload): void;
    
    /**
     * Get the webhook source identifier
     */
    public function getSource(): string;
    
    /**
     * Determine if this strategy can handle the webhook
     */
    public function canHandle(Request $request): bool;
}