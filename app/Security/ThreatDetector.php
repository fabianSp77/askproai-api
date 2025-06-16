<?php

namespace App\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SecurityAlertNotification;
use App\Models\User;

class ThreatDetector
{
    /**
     * Suspicious patterns to detect
     */
    private array $suspiciousPatterns = [
        'sql_injection' => [
            'union select', 'drop table', 'insert into',
            'delete from', 'update set', 'script>', '<script'
        ],
        'path_traversal' => [
            '../', '..\\', '%2e%2e/', '%252e%252e/'
        ],
        'xss_attempt' => [
            '<script', 'javascript:', 'onerror=', 'onload='
        ],
        'command_injection' => [
            '; cat ', '| nc ', '&& wget', '$(', '`'
        ]
    ];

    /**
     * Check request for threats
     */
    public function analyze(Request $request): array
    {
        $threats = [];

        // Check all input data
        $inputData = array_merge(
            $request->all(),
            $request->headers->all()
        );

        foreach ($inputData as $key => $value) {
            if (is_string($value)) {
                $detected = $this->detectThreats($value);
                if (!empty($detected)) {
                    $threats[] = [
                        'field' => $key,
                        'value' => substr($value, 0, 100), // Truncate for logging
                        'threats' => $detected,
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'timestamp' => now()
                    ];
                }
            }
        }

        // Check for rate anomalies
        $rateAnomaly = $this->detectRateAnomaly($request);
        if ($rateAnomaly) {
            $threats[] = $rateAnomaly;
        }

        // Log and alert if threats detected
        if (!empty($threats)) {
            $this->handleThreats($threats, $request);
        }

        return $threats;
    }

    /**
     * Detect threats in a string
     */
    private function detectThreats(string $value): array
    {
        $detected = [];
        $lowerValue = strtolower($value);

        foreach ($this->suspiciousPatterns as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($lowerValue, strtolower($pattern)) !== false) {
                    $detected[] = $type;
                    break; // One detection per type is enough
                }
            }
        }

        return array_unique($detected);
    }

    /**
     * Detect rate anomalies
     */
    private function detectRateAnomaly(Request $request): ?array
    {
        $key = 'request_count:' . $request->ip();
        $count = Cache::increment($key);
        
        // Reset counter every hour
        if ($count === 1) {
            Cache::put($key, 1, 3600);
        }

        // Anomaly threshold: 1000 requests per hour from single IP
        if ($count > 1000) {
            return [
                'type' => 'rate_anomaly',
                'ip' => $request->ip(),
                'request_count' => $count,
                'period' => 'hour',
                'timestamp' => now()
            ];
        }

        return null;
    }

    /**
     * Handle detected threats
     */
    private function handleThreats(array $threats, Request $request): void
    {
        // Log the threat
        Log::channel('security')->warning('Security threat detected', [
            'threats' => $threats,
            'url' => $request->fullUrl(),
            'method' => $request->method()
        ]);

        // Store in cache for analysis
        $threatKey = 'threats:' . date('Y-m-d');
        $dailyThreats = Cache::get($threatKey, []);
        $dailyThreats[] = [
            'threats' => $threats,
            'timestamp' => now()->toIso8601String()
        ];
        Cache::put($threatKey, $dailyThreats, 86400); // 24 hours

        // Alert if critical
        if ($this->isCriticalThreat($threats)) {
            $this->sendSecurityAlert($threats, $request);
        }
    }

    /**
     * Check if threat is critical
     */
    private function isCriticalThreat(array $threats): bool
    {
        $criticalTypes = ['sql_injection', 'command_injection'];
        
        foreach ($threats as $threat) {
            if (isset($threat['threats'])) {
                foreach ($threat['threats'] as $type) {
                    if (in_array($type, $criticalTypes)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Send security alert
     */
    private function sendSecurityAlert(array $threats, Request $request): void
    {
        // Get admin users
        $admins = User::role('super_admin')->get();

        foreach ($admins as $admin) {
            Notification::send($admin, new SecurityAlertNotification($threats, $request));
        }
    }
}