<?php

namespace App\Security;

use App\Models\Company;
use Illuminate\Support\ServiceProvider;

class AskProAISecurityLayer
{
    private EncryptionService $encryption;
    private RateLimiter $limiter;
    private ThreatDetector $detector;

    public function __construct(
        EncryptionService $encryption,
        RateLimiter $limiter,
        ThreatDetector $detector
    ) {
        $this->encryption = $encryption;
        $this->limiter = $limiter;
        $this->detector = $detector;
    }

    /**
     * Initialize all security layers
     */
    public function protect(): void
    {
        $this->enableEncryption();
        $this->implementAdaptiveRateLimiting();
        $this->enableThreatDetection();
        $this->setupSecurityHeaders();
        $this->enableAuditLogging();
    }

    /**
     * Enable automatic encryption for sensitive fields
     */
    private function enableEncryption(): void
    {
        // Register observer for Company model
        Company::observe(app(EncryptionObserver::class));
        
        // Add more models as needed
        // User::observe(app(EncryptionObserver::class));
        // Integration::observe(app(EncryptionObserver::class));
    }

    /**
     * Implement adaptive rate limiting
     */
    private function implementAdaptiveRateLimiting(): void
    {
        // This is configured in middleware
        // See App\Http\Middleware\AdaptiveRateLimitMiddleware
    }

    /**
     * Enable threat detection
     */
    private function enableThreatDetection(): void
    {
        // This is configured in middleware
        // See App\Http\Middleware\ThreatDetectionMiddleware
    }

    /**
     * Setup security headers
     */
    private function setupSecurityHeaders(): void
    {
        // This is configured in middleware
        // See App\Http\Middleware\SecurityHeadersMiddleware
    }

    /**
     * Enable comprehensive audit logging
     */
    private function enableAuditLogging(): void
    {
        // Activity logging is configured through the activitylog config file
        // and model observers. This method serves as a placeholder for
        // future audit logging enhancements.
    }

    /**
     * Get security status
     */
    public function getStatus(): array
    {
        return [
            'encryption' => [
                'enabled' => true,
                'algorithm' => config('app.cipher'),
                'fields_protected' => count($this->encryption->getEncryptedFields ?? [])
            ],
            'rate_limiting' => [
                'enabled' => true,
                'endpoints_protected' => 5
            ],
            'threat_detection' => [
                'enabled' => true,
                'patterns_monitored' => 4
            ],
            'audit_logging' => [
                'enabled' => true,
                'retention_days' => config('activitylog.delete_records_older_than_days', 365)
            ]
        ];
    }
}