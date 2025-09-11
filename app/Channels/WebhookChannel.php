<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WebhookChannel
{
    /**
     * Send the given notification via webhook.
     */
    public function send($notifiable, Notification $notification)
    {
        // Get webhook URL from tenant settings
        $webhookUrl = $notifiable->tenant->webhook_url ?? null;
        
        if (!$webhookUrl) {
            return;
        }
        
        // Get the webhook representation of the notification
        if (!method_exists($notification, 'toWebhook')) {
            return;
        }
        
        $data = $notification->toWebhook($notifiable);
        
        // Add metadata
        $payload = array_merge($data, [
            'notification_id' => uniqid('notif_'),
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'signature' => $this->generateSignature($data, $notifiable->tenant->webhook_secret)
        ]);
        
        // Send with retry logic
        $this->sendWithRetry($webhookUrl, $payload, $notifiable->tenant->id);
    }
    
    /**
     * Send webhook with exponential backoff retry
     */
    protected function sendWithRetry(string $url, array $payload, string $tenantId, int $attempt = 1): void
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $payload['signature'],
                    'X-Webhook-Timestamp' => $payload['timestamp'],
                    'X-Webhook-Id' => $payload['notification_id']
                ])
                ->post($url, $payload);
            
            if ($response->successful()) {
                // Log successful delivery
                $this->logDelivery($tenantId, $payload['notification_id'], 'success');
                return;
            }
            
            // Handle non-2xx responses
            if ($response->status() >= 400 && $response->status() < 500) {
                // Client error - don't retry
                $this->logDelivery($tenantId, $payload['notification_id'], 'failed', $response->status());
                $this->disableWebhookIfNeeded($tenantId, $response->status());
                return;
            }
            
            // Server error - retry with backoff
            if ($attempt < 3) {
                $delay = pow(2, $attempt) * 1000; // Exponential backoff: 2s, 4s, 8s
                
                dispatch(function () use ($url, $payload, $tenantId, $attempt) {
                    $this->sendWithRetry($url, $payload, $tenantId, $attempt + 1);
                })->delay(now()->addMilliseconds($delay));
            } else {
                // Max retries reached
                $this->logDelivery($tenantId, $payload['notification_id'], 'failed', $response->status());
                $this->incrementFailureCount($tenantId);
            }
            
        } catch (\Exception $e) {
            Log::error('Webhook delivery failed', [
                'tenant_id' => $tenantId,
                'url' => $url,
                'error' => $e->getMessage(),
                'attempt' => $attempt
            ]);
            
            // Retry on network errors
            if ($attempt < 3) {
                $delay = pow(2, $attempt) * 1000;
                
                dispatch(function () use ($url, $payload, $tenantId, $attempt) {
                    $this->sendWithRetry($url, $payload, $tenantId, $attempt + 1);
                })->delay(now()->addMilliseconds($delay));
            } else {
                $this->logDelivery($tenantId, $payload['notification_id'], 'error');
                $this->incrementFailureCount($tenantId);
            }
        }
    }
    
    /**
     * Generate HMAC signature for webhook payload
     */
    protected function generateSignature(array $data, ?string $secret): string
    {
        if (!$secret) {
            $secret = config('app.key');
        }
        
        $payload = json_encode($data);
        return hash_hmac('sha256', $payload, $secret);
    }
    
    /**
     * Log webhook delivery attempt
     */
    protected function logDelivery(string $tenantId, string $notificationId, string $status, ?int $httpCode = null): void
    {
        \DB::table('webhook_deliveries')->insert([
            'tenant_id' => $tenantId,
            'notification_id' => $notificationId,
            'status' => $status,
            'http_code' => $httpCode,
            'delivered_at' => $status === 'success' ? now() : null,
            'created_at' => now()
        ]);
    }
    
    /**
     * Increment failure count and potentially disable webhook
     */
    protected function incrementFailureCount(string $tenantId): void
    {
        $key = "webhook.failures.{$tenantId}";
        $failures = Cache::increment($key);
        
        // Disable webhook after 10 consecutive failures
        if ($failures >= 10) {
            $tenant = \App\Models\Tenant::find($tenantId);
            if ($tenant) {
                $tenant->update([
                    'webhook_url' => null,
                    'webhook_disabled_at' => now(),
                    'webhook_disabled_reason' => 'Too many consecutive failures'
                ]);
                
                Log::warning('Webhook disabled due to failures', [
                    'tenant_id' => $tenantId,
                    'failures' => $failures
                ]);
            }
            
            Cache::forget($key);
        } else {
            // Keep failure count for 24 hours
            Cache::put($key, $failures, 86400);
        }
    }
    
    /**
     * Disable webhook for specific HTTP status codes
     */
    protected function disableWebhookIfNeeded(string $tenantId, int $statusCode): void
    {
        // Disable on 410 Gone or 404 Not Found
        if (in_array($statusCode, [404, 410])) {
            $tenant = \App\Models\Tenant::find($tenantId);
            if ($tenant) {
                $tenant->update([
                    'webhook_url' => null,
                    'webhook_disabled_at' => now(),
                    'webhook_disabled_reason' => "HTTP {$statusCode} received"
                ]);
                
                Log::info('Webhook disabled due to HTTP status', [
                    'tenant_id' => $tenantId,
                    'status_code' => $statusCode
                ]);
            }
        }
    }
}