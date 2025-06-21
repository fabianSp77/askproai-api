<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\SecurityLog;

class SecurityMonitor
{
    private array $config;
    private AlertingService $alerting;

    public function __construct(AlertingService $alerting)
    {
        $this->config = config('monitoring.security');
        $this->alerting = $alerting;
    }

    /**
     * Log a security event
     */
    public function logEvent(string $type, Request $request, array $data = []): void
    {
        if (!$this->config['enabled'] ?? true) {
            return;
        }

        $event = [
            'type' => $type,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'company_id' => auth()->user()?->company_id,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'data' => $data,
            'timestamp' => now(),
        ];

        // Log to database
        SecurityLog::create($event);

        // Log to file
        Log::channel('security')->info("Security event: $type", $event);

        // Check if this event should trigger an alert
        $this->checkForAlerts($type, $event);

        // Track in cache for rate limiting
        $this->trackEvent($type, $request->ip());
    }

    /**
     * Check for failed login attempts
     */
    public function checkFailedLogin(Request $request, string $email): void
    {
        $ip = $request->ip();
        $cacheKey = "failed_logins:$ip";
        $attempts = Cache::get($cacheKey, 0) + 1;
        
        Cache::put($cacheKey, $attempts, now()->addMinutes(15));

        $this->logEvent('failed_login', $request, [
            'email' => $email,
            'attempts' => $attempts,
        ]);

        // Alert on multiple failed attempts
        if ($attempts >= 5) {
            $this->alerting->alert('security_breach_attempt', [
                'attempts' => $attempts,
                'ip' => $ip,
            ]);
        }
    }

    /**
     * Check for suspicious activity
     */
    public function checkSuspiciousActivity(Request $request, string $reason): void
    {
        $this->logEvent('suspicious_activity', $request, [
            'reason' => $reason,
        ]);

        // Check if IP should be blocked
        if ($this->shouldBlockIp($request->ip())) {
            $this->blockIp($request->ip());
        }
    }

    /**
     * Monitor API key usage
     */
    public function monitorApiKeyUsage(string $apiKey, Request $request): void
    {
        if (!$this->config['events']['api_key_usage'] ?? true) {
            return;
        }

        $hashedKey = substr(hash('sha256', $apiKey), 0, 8);
        $cacheKey = "api_key_usage:$hashedKey";
        
        $usage = Cache::get($cacheKey, [
            'count' => 0,
            'ips' => [],
            'last_used' => null,
        ]);

        $usage['count']++;
        $usage['ips'][$request->ip()] = ($usage['ips'][$request->ip()] ?? 0) + 1;
        $usage['last_used'] = now();

        Cache::put($cacheKey, $usage, now()->addHours(24));

        // Check for unusual patterns
        if (count($usage['ips']) > 10) {
            $this->logEvent('api_key_suspicious_usage', $request, [
                'key_hash' => $hashedKey,
                'unique_ips' => count($usage['ips']),
            ]);
        }
    }

    /**
     * Monitor privilege escalation attempts
     */
    public function monitorPrivilegeEscalation(Request $request, string $action): void
    {
        if (!$this->config['events']['privilege_escalations'] ?? true) {
            return;
        }

        $this->logEvent('privilege_escalation_attempt', $request, [
            'action' => $action,
            'user_role' => auth()->user()?->role,
        ]);
    }

    /**
     * Monitor data exports
     */
    public function monitorDataExport(Request $request, string $type, int $recordCount): void
    {
        if (!$this->config['events']['data_exports'] ?? true) {
            return;
        }

        $this->logEvent('data_export', $request, [
            'export_type' => $type,
            'record_count' => $recordCount,
        ]);

        // Alert on large exports
        if ($recordCount > 10000) {
            $this->alerting->alert('large_data_export', [
                'type' => $type,
                'count' => $recordCount,
                'user' => auth()->user()?->email,
            ]);
        }
    }

    /**
     * Check rate limit violations
     */
    public function checkRateLimitViolation(Request $request, string $limit): void
    {
        if (!$this->config['rate_limiting']['track_violations'] ?? true) {
            return;
        }

        $ip = $request->ip();
        $cacheKey = "rate_limit_violations:$ip";
        $violations = Cache::get($cacheKey, 0) + 1;
        
        Cache::put($cacheKey, $violations, now()->addHours(1));

        $this->logEvent('rate_limit_violation', $request, [
            'limit' => $limit,
            'violations' => $violations,
        ]);

        // Alert on excessive violations
        if ($violations >= ($this->config['rate_limiting']['alert_threshold'] ?? 100)) {
            $this->alerting->alert('excessive_rate_limit_violations', [
                'ip' => $ip,
                'violations' => $violations,
            ]);
        }
    }

    /**
     * Track event for analysis
     */
    private function trackEvent(string $type, string $ip): void
    {
        $cacheKey = "security_events:$type:$ip";
        $events = Cache::get($cacheKey, []);
        $events[] = now();
        
        // Keep only events from last hour
        $events = array_filter($events, function ($timestamp) {
            return $timestamp->isAfter(now()->subHour());
        });

        Cache::put($cacheKey, array_values($events), now()->addHours(2));
    }

    /**
     * Check if event should trigger alert
     */
    private function checkForAlerts(string $type, array $event): void
    {
        // Record event for alerting service
        $this->alerting->recordEvent("security_$type");

        // Check specific event types
        switch ($type) {
            case 'suspicious_activity':
            case 'privilege_escalation_attempt':
                $this->alerting->alert('security_breach_attempt', [
                    'type' => $type,
                    'ip' => $event['ip_address'],
                ]);
                break;
        }
    }

    /**
     * Check if IP should be blocked
     */
    private function shouldBlockIp(string $ip): bool
    {
        $events = [];
        $types = ['failed_login', 'suspicious_activity', 'rate_limit_violation'];
        
        foreach ($types as $type) {
            $cacheKey = "security_events:$type:$ip";
            $typeEvents = Cache::get($cacheKey, []);
            $events = array_merge($events, $typeEvents);
        }

        // Block if more than 20 security events in last hour
        return count($events) > 20;
    }

    /**
     * Block an IP address
     */
    private function blockIp(string $ip): void
    {
        $cacheKey = "blocked_ips";
        $blockedIps = Cache::get($cacheKey, []);
        
        $blockedIps[$ip] = [
            'blocked_at' => now(),
            'expires_at' => now()->addHours(24),
        ];

        Cache::put($cacheKey, $blockedIps, now()->addDays(7));

        Log::channel('security')->warning('IP blocked', [
            'ip' => $ip,
            'expires_at' => $blockedIps[$ip]['expires_at'],
        ]);
    }

    /**
     * Check if IP is blocked
     */
    public function isIpBlocked(string $ip): bool
    {
        $blockedIps = Cache::get('blocked_ips', []);
        
        if (!isset($blockedIps[$ip])) {
            return false;
        }

        // Check if block has expired
        if (now()->isAfter($blockedIps[$ip]['expires_at'])) {
            unset($blockedIps[$ip]);
            Cache::put('blocked_ips', $blockedIps, now()->addDays(7));
            return false;
        }

        return true;
    }

    /**
     * Get security metrics
     */
    public function getMetrics(): array
    {
        $metrics = [
            'failed_logins_24h' => SecurityLog::where('type', 'failed_login')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'suspicious_activities_24h' => SecurityLog::where('type', 'suspicious_activity')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'blocked_ips' => count(Cache::get('blocked_ips', [])),
            'rate_limit_violations_1h' => SecurityLog::where('type', 'rate_limit_violation')
                ->where('created_at', '>=', now()->subHour())
                ->count(),
        ];

        return $metrics;
    }
}