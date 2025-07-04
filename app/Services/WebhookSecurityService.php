<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class WebhookSecurityService
{
    /**
     * Standard rate limit for all webhook endpoints
     */
    public const STANDARD_RATE_LIMIT = '60,1'; // 60 requests per minute
    
    /**
     * Health check rate limit (lower since it's just for monitoring)
     */
    public const HEALTH_CHECK_RATE_LIMIT = '10,1'; // 10 requests per minute
    
    /**
     * Get standard webhook middleware stack for a provider
     */
    public static function getMiddlewareStack(string $provider): array
    {
        $middleware = [];
        
        // Add provider-specific signature verification
        switch ($provider) {
            case 'retell':
                $middleware[] = 'verify.retell.signature';
                break;
            case 'calcom':
                $middleware[] = 'calcom.signature';
                break;
            case 'stripe':
                $middleware[] = 'verify.stripe.signature';
                break;
            case 'unified':
                // Unified endpoint will auto-detect and verify internally
                break;
            default:
                throw new \InvalidArgumentException("Unknown webhook provider: {$provider}");
        }
        
        // Add common security middleware
        $middleware = array_merge($middleware, [
            'webhook.replay.protection',
            'throttle:' . self::STANDARD_RATE_LIMIT,
            'monitoring'
        ]);
        
        // Add IP whitelisting if enabled
        if (Config::get("services.{$provider}.verify_ip", false)) {
            $middleware[] = 'ip.whitelist:' . $provider;
        }
        
        return $middleware;
    }
    
    /**
     * Get health check middleware
     */
    public static function getHealthCheckMiddleware(): array
    {
        return [
            'throttle:' . self::HEALTH_CHECK_RATE_LIMIT
        ];
    }
    
    /**
     * Verify webhook configuration is secure
     */
    public static function verifyConfiguration(): array
    {
        $issues = [];
        
        // Check webhook secrets are configured
        $providers = ['retell', 'calcom', 'stripe'];
        foreach ($providers as $provider) {
            $secretKey = match($provider) {
                'retell' => 'RETELL_WEBHOOK_SECRET',
                'calcom' => 'CALCOM_WEBHOOK_SECRET',
                'stripe' => 'STRIPE_WEBHOOK_SECRET',
            };
            
            if (empty(env($secretKey))) {
                $issues[] = "Missing webhook secret: {$secretKey}";
            }
        }
        
        // Check replay protection window
        if (!Config::get('webhooks.replay_window')) {
            $issues[] = 'Webhook replay protection window not configured';
        }
        
        return $issues;
    }
    
    /**
     * Log webhook security event
     */
    public static function logSecurityEvent(string $event, array $context = []): void
    {
        \Log::channel('webhook_security')->info($event, array_merge([
            'timestamp' => now()->toIso8601String(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $context));
    }
}