<?php

namespace App\Services\Resilience;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Resilience Metrics - Circuit breaker monitoring and observability
 *
 * Tracks:
 * - Circuit breaker state transitions
 * - Failure patterns and trends
 * - Recovery times and success rates
 * - Service impact metrics
 * - SLO adherence
 */
class ResilienceMetrics
{
    /**
     * Record state transition
     *
     * @param string $serviceName Service name
     * @param string $fromState Previous state
     * @param string $toState New state
     * @param string $reason Reason for transition
     */
    public function recordStateTransition(
        string $serviceName,
        string $fromState,
        string $toState,
        string $reason = ''
    ): void {
        try {
            $transition = [
                'service' => $serviceName,
                'from_state' => $fromState,
                'to_state' => $toState,
                'reason' => $reason,
                'timestamp' => now()->toIso8601String(),
                'unix_timestamp' => now()->timestamp,
            ];

            // Store transition history
            $historyKey = "metrics:transitions:{$serviceName}";
            $history = Cache::get($historyKey, []);
            $history[] = $transition;

            // Keep last 1000 transitions
            if (count($history) > 1000) {
                $history = array_slice($history, -1000);
            }

            Cache::put($historyKey, $history, 86400 * 30);  // 30 days

            // Track specific state change types
            $changeKey = "metrics:transitions:{$serviceName}:{$fromState}_to_{$toState}";
            Cache::increment($changeKey);

            // Log for visibility
            Log::info("🔄 Circuit breaker state transition", [
                'service' => $serviceName,
                'from' => $fromState,
                'to' => $toState,
                'reason' => $reason,
            ]);

        } catch (Exception $e) {
            Log::warning("Failed to record state transition", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record operation attempt
     *
     * @param string $serviceName Service name
     * @param bool $success Whether operation succeeded
     * @param int $latencyMs Operation latency
     */
    public function recordOperationAttempt(
        string $serviceName,
        bool $success,
        int $latencyMs = 0
    ): void {
        try {
            $metric = [
                'service' => $serviceName,
                'success' => $success,
                'latency_ms' => $latencyMs,
                'timestamp' => now()->toIso8601String(),
            ];

            // Store operation metrics
            $metricsKey = "metrics:operations:{$serviceName}";
            $metrics = Cache::get($metricsKey, []);
            $metrics[] = $metric;

            // Keep last 10000 operations
            if (count($metrics) > 10000) {
                $metrics = array_slice($metrics, -10000);
            }

            Cache::put($metricsKey, $metrics, 86400);

            // Increment success/failure counters
            if ($success) {
                Cache::increment("metrics:successes:{$serviceName}");
            } else {
                Cache::increment("metrics:failures:{$serviceName}");
            }

            // Track latency percentiles
            $this->recordLatencyMetric($serviceName, $latencyMs);

        } catch (Exception $e) {
            Log::warning("Failed to record operation attempt", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record latency metric for percentile calculation
     *
     * @param string $serviceName Service name
     * @param int $latencyMs Latency in milliseconds
     */
    private function recordLatencyMetric(string $serviceName, int $latencyMs): void
    {
        try {
            $latencyKey = "metrics:latencies:{$serviceName}";
            $latencies = Cache::get($latencyKey, []);
            $latencies[] = $latencyMs;

            // Keep last 1000 samples
            if (count($latencies) > 1000) {
                $latencies = array_slice($latencies, -1000);
            }

            Cache::put($latencyKey, $latencies, 3600);  // 1 hour

        } catch (Exception $e) {
            Log::debug("Failed to record latency metric", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get resilience metrics for service
     *
     * @param string $serviceName Service name
     * @return array Comprehensive metrics
     */
    public function getMetrics(string $serviceName): array
    {
        try {
            $successes = Cache::get("metrics:successes:{$serviceName}", 0);
            $failures = Cache::get("metrics:failures:{$serviceName}", 0);
            $total = $successes + $failures;

            $latencies = Cache::get("metrics:latencies:{$serviceName}", []);
            $latencyMetrics = $this->calculateLatencyMetrics($latencies);

            // Get state transitions
            $transitions = Cache::get("metrics:transitions:{$serviceName}", []);
            $transitionMetrics = $this->analyzeTransitions($transitions);

            return [
                'timestamp' => now()->toIso8601String(),
                'service' => $serviceName,
                'requests' => [
                    'total' => $total,
                    'successes' => $successes,
                    'failures' => $failures,
                ],
                'rates' => [
                    'success_rate' => $total > 0 ? round(($successes / $total) * 100, 2) : 0,
                    'failure_rate' => $total > 0 ? round(($failures / $total) * 100, 2) : 0,
                ],
                'latency' => $latencyMetrics,
                'state_transitions' => $transitionMetrics,
            ];

        } catch (Exception $e) {
            Log::warning("Failed to get metrics", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Calculate latency percentiles
     *
     * @param array $latencies Latency samples
     * @return array Percentile metrics
     */
    private function calculateLatencyMetrics(array $latencies): array
    {
        if (empty($latencies)) {
            return [
                'p50_ms' => 0,
                'p95_ms' => 0,
                'p99_ms' => 0,
                'avg_ms' => 0,
            ];
        }

        sort($latencies);
        $count = count($latencies);

        return [
            'p50_ms' => round($latencies[floor($count * 0.50)] ?? 0, 2),
            'p95_ms' => round($latencies[floor($count * 0.95)] ?? 0, 2),
            'p99_ms' => round($latencies[floor($count * 0.99)] ?? 0, 2),
            'avg_ms' => round(array_sum($latencies) / $count, 2),
            'max_ms' => max($latencies),
            'min_ms' => min($latencies),
        ];
    }

    /**
     * Analyze state transitions
     *
     * @param array $transitions Transition history
     * @return array Transition analysis
     */
    private function analyzeTransitions(array $transitions): array
    {
        if (empty($transitions)) {
            return [
                'total_transitions' => 0,
                'to_open' => 0,
                'to_half_open' => 0,
                'to_closed' => 0,
            ];
        }

        $toOpen = count(array_filter($transitions, fn($t) => $t['to_state'] === 'open'));
        $toHalfOpen = count(array_filter($transitions, fn($t) => $t['to_state'] === 'half_open'));
        $toClosed = count(array_filter($transitions, fn($t) => $t['to_state'] === 'closed'));

        // Calculate recovery time (average time from OPEN to CLOSED)
        $recoveryTimes = [];
        $openTime = null;

        foreach ($transitions as $transition) {
            if ($transition['to_state'] === 'open') {
                $openTime = $transition['unix_timestamp'];
            }
            if ($transition['to_state'] === 'closed' && $openTime !== null) {
                $recoveryTimes[] = $transition['unix_timestamp'] - $openTime;
                $openTime = null;
            }
        }

        $avgRecoveryTime = count($recoveryTimes) > 0
            ? array_sum($recoveryTimes) / count($recoveryTimes)
            : 0;

        return [
            'total_transitions' => count($transitions),
            'to_open' => $toOpen,
            'to_half_open' => $toHalfOpen,
            'to_closed' => $toClosed,
            'avg_recovery_time_seconds' => round($avgRecoveryTime, 2),
        ];
    }

    /**
     * Get SLO metrics (Service Level Objectives)
     *
     * Default SLOs:
     * - 99.9% availability
     * - p99 latency < 1 second
     * - recovery time < 5 minutes
     *
     * @param string $serviceName Service name
     * @return array SLO adherence
     */
    public function getSloMetrics(string $serviceName): array
    {
        try {
            $metrics = $this->getMetrics($serviceName);

            $successRate = $metrics['rates']['success_rate'] ?? 0;
            $p99Latency = $metrics['latency']['p99_ms'] ?? 0;
            $avgRecoveryTime = $metrics['state_transitions']['avg_recovery_time_seconds'] ?? 0;

            // Define SLOs
            $slos = [
                'availability' => [
                    'target' => 99.9,
                    'actual' => $successRate,
                    'met' => $successRate >= 99.9,
                    'unit' => '%',
                ],
                'latency_p99' => [
                    'target' => 1000,
                    'actual' => $p99Latency,
                    'met' => $p99Latency <= 1000,
                    'unit' => 'ms',
                ],
                'recovery_time' => [
                    'target' => 300,
                    'actual' => $avgRecoveryTime,
                    'met' => $avgRecoveryTime <= 300,
                    'unit' => 'seconds',
                ],
            ];

            $allMet = collect($slos)->every(fn($slo) => $slo['met']);

            return [
                'service' => $serviceName,
                'timestamp' => now()->toIso8601String(),
                'all_slos_met' => $allMet,
                'slos' => $slos,
                'overall_health' => $allMet ? 'healthy' : 'degraded',
            ];

        } catch (Exception $e) {
            Log::warning("Failed to get SLO metrics", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get trends for all services
     *
     * Shows overall system resilience trends
     *
     * @return array System trends
     */
    public function getSystemTrends(): array
    {
        try {
            $services = ['calcom', 'retell', 'database', 'redis'];
            $trends = [];

            foreach ($services as $service) {
                $metrics = $this->getMetrics($service);
                $slo = $this->getSloMetrics($service);

                $trends[$service] = [
                    'success_rate' => $metrics['rates']['success_rate'] ?? 0,
                    'p99_latency_ms' => $metrics['latency']['p99_ms'] ?? 0,
                    'slos_met' => $slo['all_slos_met'] ?? false,
                    'health' => $slo['overall_health'] ?? 'unknown',
                ];
            }

            return [
                'timestamp' => now()->toIso8601String(),
                'services' => $trends,
                'system_health' => $this->calculateSystemHealth($trends),
            ];

        } catch (Exception $e) {
            Log::warning("Failed to get system trends", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Calculate overall system health
     *
     * @param array $trends Service trends
     * @return string System health
     */
    private function calculateSystemHealth(array $trends): string
    {
        $healthyServices = count(array_filter($trends, fn($t) => $t['health'] === 'healthy'));
        $totalServices = count($trends);

        if ($healthyServices === $totalServices) {
            return 'healthy';
        } elseif ($healthyServices >= ($totalServices / 2)) {
            return 'degraded';
        } else {
            return 'critical';
        }
    }

    /**
     * Get metrics for dashboard display
     *
     * @return array Dashboard metrics
     */
    public function getDashboardMetrics(): array
    {
        try {
            $systemTrends = $this->getSystemTrends();

            return [
                'timestamp' => now()->toIso8601String(),
                'system_health' => $systemTrends['system_health'],
                'services' => array_map(function ($service, $data) {
                    return [
                        'name' => $service,
                        'success_rate' => $data['success_rate'],
                        'latency_p99_ms' => $data['p99_latency_ms'],
                        'slos_met' => $data['slos_met'],
                        'status' => $data['health'],
                    ];
                }, array_keys($systemTrends['services']), array_values($systemTrends['services'])),
                'alerts' => $this->generateAlerts($systemTrends),
            ];

        } catch (Exception $e) {
            Log::warning("Failed to get dashboard metrics", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Generate alerts based on metrics
     *
     * @param array $trends System trends
     * @return array Active alerts
     */
    private function generateAlerts(array $trends): array
    {
        $alerts = [];

        foreach ($trends['services'] as $service => $data) {
            if ($data['health'] !== 'healthy') {
                $alerts[] = [
                    'level' => 'warning',
                    'service' => $service,
                    'message' => "Service in {$data['health']} state",
                    'metric' => "success_rate: {$data['success_rate']}%",
                ];
            }

            if ($data['latency_p99_ms'] > 1000) {
                $alerts[] = [
                    'level' => 'warning',
                    'service' => $service,
                    'message' => 'High latency detected',
                    'metric' => "p99: {$data['latency_p99_ms']}ms",
                ];
            }

            if (!$data['slos_met']) {
                $alerts[] = [
                    'level' => 'critical',
                    'service' => $service,
                    'message' => 'SLO violation detected',
                    'action' => 'Immediate investigation required',
                ];
            }
        }

        return $alerts;
    }

    /**
     * Clear old metrics (cleanup)
     *
     * @return array Cleanup results
     */
    public function clearOldMetrics(): array
    {
        try {
            $services = ['calcom', 'retell', 'database', 'redis'];
            $cleared = [];

            foreach ($services as $service) {
                Cache::forget("metrics:transitions:{$service}");
                Cache::forget("metrics:operations:{$service}");
                Cache::forget("metrics:latencies:{$service}");
                Cache::forget("metrics:successes:{$service}");
                Cache::forget("metrics:failures:{$service}");

                $cleared[] = $service;
            }

            Log::info("✓ Old metrics cleared", ['services' => $cleared]);

            return [
                'status' => 'success',
                'cleared_services' => $cleared,
                'timestamp' => now()->toIso8601String(),
            ];

        } catch (Exception $e) {
            Log::warning("Failed to clear metrics", [
                'error' => $e->getMessage(),
            ]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
