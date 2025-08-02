<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Business Portal Performance Monitoring Service
 * 
 * Extends the existing PerformanceMonitoringService with Business Portal-specific
 * metrics, SLA monitoring, and alerting capabilities.
 */
class BusinessPortalPerformanceService extends PerformanceMonitoringService
{
    // Business Portal SLA Targets (milliseconds)
    protected array $slaTargets = [
        '/business/api/calls' => 200,
        '/business/api/dashboard' => 200,
        '/business/dashboard' => 300,
        '/business/calls' => 400,
        '/business/calls/*' => 200,
        '/business/settings' => 250,
        '/business/team' => 300,
        '/business/api/appointments' => 150,
        '/business/api/customers' => 180,
    ];

    // Alert thresholds as multipliers of SLA targets
    protected array $alertMultipliers = [
        'warning' => 1.2,   // 20% above SLA
        'critical' => 1.5,  // 50% above SLA
        'emergency' => 2.0, // 100% above SLA
    ];

    // Endpoint categories for prioritized monitoring
    protected array $endpointCategories = [
        'critical' => ['/business/api/calls', '/business/api/dashboard', '/business/api/appointments'],
        'high' => ['/business/calls', '/business/calls/*', '/business/dashboard'],
        'medium' => ['/business/settings', '/business/team', '/business/api/customers'],
        'low' => ['/business/feedback', '/business/help'],
    ];

    /**
     * Record Business Portal request with SLA monitoring
     */
    public function recordPortalRequest(string $endpoint, float $duration, int $statusCode, array $metadata = []): void
    {
        // Call parent method for basic recording
        $this->recordRequest($endpoint, $duration, $statusCode, $metadata);

        // Business Portal specific processing
        $this->checkSLACompliance($endpoint, $duration, $statusCode);
        $this->updateBusinessMetrics($endpoint, $duration, $statusCode, $metadata);
        $this->trackUserExperience($endpoint, $duration, $statusCode, $metadata);
    }

    /**
     * Check SLA compliance and trigger alerts if needed
     */
    public function checkSLACompliance(string $endpoint, float $duration, int $statusCode): void
    {
        $slaTarget = $this->getSLATarget($endpoint);
        
        if (!$slaTarget) {
            return; // No SLA defined for this endpoint
        }

        $complianceStatus = $this->calculateComplianceStatus($duration, $slaTarget);
        
        // Record SLA compliance metric
        $this->record('portal.sla.compliance', $complianceStatus['is_compliant'] ? 1 : 0, [
            'endpoint' => $endpoint,
            'category' => $this->getEndpointCategory($endpoint),
            'target_ms' => $slaTarget,
            'actual_ms' => $duration,
        ]);

        // Trigger alerts based on compliance
        if (!$complianceStatus['is_compliant']) {
            $this->triggerSLAAlert($endpoint, $duration, $slaTarget, $complianceStatus['severity']);
        }

        // Update real-time SLA status
        $this->updateSLAStatus($endpoint, $complianceStatus);
    }

    /**
     * Get comprehensive Business Portal performance dashboard data
     */
    public function getPortalDashboardData(string $timeframe = 'last_hour'): array
    {
        $since = $this->getTimeframeBoundary($timeframe);
        
        return [
            'overview' => $this->getPerformanceOverview($since),
            'sla_compliance' => $this->getSLAComplianceData($since),
            'endpoint_performance' => $this->getEndpointPerformanceData($since),
            'error_analysis' => $this->getErrorAnalysis($since),
            'user_experience' => $this->getUserExperienceMetrics($since),
            'resource_utilization' => $this->getResourceUtilization($since),
            'active_alerts' => $this->getActiveAlerts(),
            'performance_trends' => $this->getPerformanceTrends($timeframe),
        ];
    }

    /**
     * Get performance overview metrics
     */
    protected function getPerformanceOverview(Carbon $since): array
    {
        $metrics = $this->getPortalMetrics($since);
        
        return [
            'total_requests' => $metrics['total_requests'] ?? 0,
            'avg_response_time' => round($metrics['avg_response_time'] ?? 0, 2),
            'p95_response_time' => round($metrics['p95_response_time'] ?? 0, 2),
            'error_rate' => round(($metrics['error_count'] ?? 0) / max($metrics['total_requests'] ?? 1, 1) * 100, 2),
            'uptime_percentage' => $this->calculateUptime($since),
            'sla_compliance_percentage' => $this->calculateOverallSLACompliance($since),
        ];
    }

    /**
     * Get SLA compliance data for dashboard
     */
    protected function getSLAComplianceData(Carbon $since): array
    {
        $complianceData = [];
        
        foreach ($this->slaTargets as $endpoint => $target) {
            $metrics = $this->getEndpointMetrics($endpoint, $since);
            $complianceData[$endpoint] = [
                'target_ms' => $target,
                'avg_response_time' => round($metrics['avg_response_time'] ?? 0, 2),
                'p95_response_time' => round($metrics['p95_response_time'] ?? 0, 2),
                'compliance_percentage' => $this->calculateEndpointSLACompliance($endpoint, $since),
                'status' => $this->getSLAStatusColor($endpoint, $metrics['avg_response_time'] ?? 0, $target),
                'category' => $this->getEndpointCategory($endpoint),
            ];
        }
        
        return $complianceData;
    }

    /**
     * Get endpoint performance data
     */
    protected function getEndpointPerformanceData(Carbon $since): array
    {
        $endpointData = [];
        
        foreach ($this->slaTargets as $endpoint => $target) {
            $metrics = $this->getEndpointMetrics($endpoint, $since);
            $endpointData[] = [
                'endpoint' => $endpoint,
                'requests' => $metrics['request_count'] ?? 0,
                'avg_time' => round($metrics['avg_response_time'] ?? 0, 2),
                'min_time' => round($metrics['min_response_time'] ?? 0, 2),
                'max_time' => round($metrics['max_response_time'] ?? 0, 2),
                'p50_time' => round($metrics['p50_response_time'] ?? 0, 2),
                'p95_time' => round($metrics['p95_response_time'] ?? 0, 2),
                'p99_time' => round($metrics['p99_response_time'] ?? 0, 2),
                'error_count' => $metrics['error_count'] ?? 0,
                'sla_target' => $target,
                'category' => $this->getEndpointCategory($endpoint),
            ];
        }
        
        // Sort by average response time descending
        usort($endpointData, fn($a, $b) => $b['avg_time'] <=> $a['avg_time']);
        
        return $endpointData;
    }

    /**
     * Get user experience metrics
     */
    protected function getUserExperienceMetrics(Carbon $since): array
    {
        return [
            'session_metrics' => $this->getSessionMetrics($since),
            'feature_usage' => $this->getFeatureUsageMetrics($since),
            'user_satisfaction' => $this->calculateUserSatisfactionScore($since),
            'bounce_rate' => $this->calculateBounceRate($since),
            'page_load_metrics' => $this->getPageLoadMetrics($since),
        ];
    }

    /**
     * Get active performance alerts
     */
    public function getActiveAlerts(): array
    {
        $alerts = [];
        
        // Get alerts from cache
        $alertKeys = Cache::tags(['performance_alerts'])->get('active_alert_keys', []);
        
        foreach ($alertKeys as $key) {
            $alert = Cache::get("alert:{$key}");
            if ($alert && $alert['status'] === 'active') {
                $alerts[] = $alert;
            }
        }
        
        // Sort by severity and time
        usort($alerts, function($a, $b) {
            $severityOrder = ['emergency' => 0, 'critical' => 1, 'warning' => 2];
            return ($severityOrder[$a['severity']] ?? 3) <=> ($severityOrder[$b['severity']] ?? 3);
        });
        
        return $alerts;
    }

    /**
     * Generate performance health score
     */
    public function getHealthScore(string $timeframe = 'last_hour'): array
    {
        $since = $this->getTimeframeBoundary($timeframe);
        $score = 100;
        $factors = [];
        
        // SLA Compliance factor (40% weight)
        $slaCompliance = $this->calculateOverallSLACompliance($since);
        $slaScore = min(100, $slaCompliance);
        $score = $score * 0.6 + $slaScore * 0.4;
        $factors['sla_compliance'] = ['score' => $slaScore, 'weight' => 40];
        
        // Error Rate factor (25% weight)
        $errorRate = $this->getOverallErrorRate($since);
        $errorScore = max(0, 100 - ($errorRate * 20)); // Each 1% error reduces score by 20
        $score = $score * 0.75 + $errorScore * 0.25;
        $factors['error_rate'] = ['score' => $errorScore, 'weight' => 25];
        
        // Resource Utilization factor (20% weight)
        $resourceScore = $this->getResourceHealthScore();
        $score = $score * 0.8 + $resourceScore * 0.2;
        $factors['resource_utilization'] = ['score' => $resourceScore, 'weight' => 20];
        
        // Alert Status factor (15% weight)
        $alertScore = $this->getAlertHealthScore();
        $score = $score * 0.85 + $alertScore * 0.15;
        $factors['alert_status'] = ['score' => $alertScore, 'weight' => 15];
        
        return [
            'overall_score' => round($score, 1),
            'grade' => $this->getPerformanceGrade($score),
            'factors' => $factors,
            'recommendations' => $this->getHealthRecommendations($factors),
        ];
    }

    /**
     * Trigger SLA alert
     */
    protected function triggerSLAAlert(string $endpoint, float $duration, float $target, string $severity): void
    {
        $alertId = md5($endpoint . $severity . date('YmdH')); // One alert per endpoint per severity per hour
        
        $alert = [
            'id' => $alertId,
            'type' => 'sla_breach',
            'severity' => $severity,
            'endpoint' => $endpoint,
            'actual_ms' => $duration,
            'target_ms' => $target,
            'breach_percentage' => round(($duration - $target) / $target * 100, 1),
            'category' => $this->getEndpointCategory($endpoint),
            'timestamp' => now()->toIso8601String(),
            'status' => 'active',
        ];
        
        // Store alert
        Cache::put("alert:{$alertId}", $alert, now()->addHours(24));
        
        // Add to active alerts list
        $activeAlerts = Cache::tags(['performance_alerts'])->get('active_alert_keys', []);
        if (!in_array($alertId, $activeAlerts)) {
            $activeAlerts[] = $alertId;
            Cache::tags(['performance_alerts'])->put('active_alert_keys', $activeAlerts, now()->addHours(24));
        }
        
        // Log the alert
        Log::channel('performance')->warning("SLA breach detected", $alert);
        
        // Send notifications based on severity
        $this->sendAlertNotification($alert);
    }

    /**
     * Send alert notification
     */
    protected function sendAlertNotification(array $alert): void
    {
        // For now, just log - can be extended to send emails, Slack messages, etc.
        $message = sprintf(
            "[%s] SLA Breach: %s took %dms (target: %dms, %s%% over)",
            strtoupper($alert['severity']),
            $alert['endpoint'],
            $alert['actual_ms'],
            $alert['target_ms'],
            $alert['breach_percentage']
        );
        
        Log::channel('alerts')->warning($message, $alert);
        
        // TODO: Implement email/Slack notifications based on severity
        // if ($alert['severity'] === 'critical' || $alert['severity'] === 'emergency') {
        //     Mail::to(config('monitoring.alert_email'))->send(new PerformanceAlert($alert));
        // }
    }

    /**
     * Helper methods
     */
    
    protected function getSLATarget(string $endpoint): ?float
    {
        // Try exact match first
        if (isset($this->slaTargets[$endpoint])) {
            return $this->slaTargets[$endpoint];
        }
        
        // Try pattern matching
        foreach ($this->slaTargets as $pattern => $target) {
            if (strpos($pattern, '*') !== false) {
                $regex = str_replace('*', '.*', preg_quote($pattern, '/'));
                if (preg_match("/^{$regex}$/", $endpoint)) {
                    return $target;
                }
            }
        }
        
        return null;
    }
    
    protected function calculateComplianceStatus(float $duration, float $target): array
    {
        $is_compliant = $duration <= $target;
        $breach_percentage = ($duration - $target) / $target * 100;
        
        $severity = 'compliant';
        if (!$is_compliant) {
            if ($duration > $target * $this->alertMultipliers['emergency']) {
                $severity = 'emergency';
            } elseif ($duration > $target * $this->alertMultipliers['critical']) {
                $severity = 'critical';
            } elseif ($duration > $target * $this->alertMultipliers['warning']) {
                $severity = 'warning';
            }
        }
        
        return [
            'is_compliant' => $is_compliant,
            'breach_percentage' => round($breach_percentage, 1),
            'severity' => $severity,
        ];
    }
    
    protected function getEndpointCategory(string $endpoint): string
    {
        foreach ($this->endpointCategories as $category => $endpoints) {
            if (in_array($endpoint, $endpoints)) {
                return $category;
            }
        }
        return 'uncategorized';
    }
    
    protected function getTimeframeBoundary(string $timeframe): Carbon
    {
        return match($timeframe) {
            'last_5_minutes' => now()->subMinutes(5),
            'last_15_minutes' => now()->subMinutes(15),
            'last_hour' => now()->subHour(),
            'last_6_hours' => now()->subHours(6),
            'last_24_hours' => now()->subDay(),
            'last_week' => now()->subWeek(),
            default => now()->subHour(),
        };
    }
    
    protected function getPerformanceGrade(float $score): string
    {
        if ($score >= 95) return 'A+';
        if ($score >= 90) return 'A';
        if ($score >= 85) return 'A-';
        if ($score >= 80) return 'B+';
        if ($score >= 75) return 'B';
        if ($score >= 70) return 'B-';
        if ($score >= 65) return 'C+';
        if ($score >= 60) return 'C';
        return 'F';
    }

    protected function updateBusinessMetrics(string $endpoint, float $duration, int $statusCode, array $metadata): void
    {
        // Record business-specific metrics
        $this->record('portal.endpoint.response_time', $duration, [
            'endpoint' => $endpoint,
            'status' => $statusCode,
            'category' => $this->getEndpointCategory($endpoint),
        ]);
        
        // Track feature usage
        $feature = $this->extractFeatureFromEndpoint($endpoint);
        if ($feature) {
            $this->record('portal.feature.usage', 1, [
                'feature' => $feature,
                'endpoint' => $endpoint,
            ]);
        }
    }
    
    protected function extractFeatureFromEndpoint(string $endpoint): ?string
    {
        if (strpos($endpoint, '/calls') !== false) return 'calls';
        if (strpos($endpoint, '/dashboard') !== false) return 'dashboard';
        if (strpos($endpoint, '/appointments') !== false) return 'appointments';
        if (strpos($endpoint, '/customers') !== false) return 'customers';
        if (strpos($endpoint, '/settings') !== false) return 'settings';
        if (strpos($endpoint, '/team') !== false) return 'team';
        
        return null;
    }

    // Placeholder methods for complex calculations - would be implemented based on actual data
    protected function getPortalMetrics(Carbon $since): array { return []; }
    protected function getEndpointMetrics(string $endpoint, Carbon $since): array { return []; }
    protected function calculateUptime(Carbon $since): float { return 99.95; }
    protected function calculateOverallSLACompliance(Carbon $since): float { return 98.5; }
    protected function calculateEndpointSLACompliance(string $endpoint, Carbon $since): float { return 97.8; }
    protected function getSLAStatusColor(string $endpoint, float $actual, float $target): string 
    { 
        return $actual <= $target ? 'green' : ($actual <= $target * 1.2 ? 'yellow' : 'red'); 
    }
    protected function getErrorAnalysis(Carbon $since): array { return []; }
    protected function getResourceUtilization(Carbon $since): array { return []; }
    protected function getPerformanceTrends(string $timeframe): array { return []; }
    protected function getSessionMetrics(Carbon $since): array { return []; }
    protected function getFeatureUsageMetrics(Carbon $since): array { return []; }
    protected function calculateUserSatisfactionScore(Carbon $since): float { return 8.7; }
    protected function calculateBounceRate(Carbon $since): float { return 12.5; }
    protected function getPageLoadMetrics(Carbon $since): array { return []; }
    protected function getOverallErrorRate(Carbon $since): float { return 0.5; }
    protected function getResourceHealthScore(): float { return 85.0; }
    protected function getAlertHealthScore(): float { return 95.0; }
    protected function getHealthRecommendations(array $factors): array { return []; }
    protected function updateSLAStatus(string $endpoint, array $complianceStatus): void { }
    protected function trackUserExperience(string $endpoint, float $duration, int $statusCode, array $metadata): void { }
}