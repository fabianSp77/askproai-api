<?php

namespace App\Services\Resilience;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Health Check Orchestrator - Multi-service monitoring
 *
 * Coordinates health checks across all services:
 * - Periodic health checks (5s, 30s, 60s intervals)
 * - Deep health verification (HTTP pings, DB queries, etc.)
 * - Probe-based detection (detects issues before circuit open)
 * - Automated alerting on degradation
 * - Health history tracking
 */
class HealthCheckOrchestrator
{
    /**
     * Health check probes for each service
     */
    private const HEALTH_PROBES = [
        'calcom' => [
            'interval_seconds' => 30,
            'timeout_seconds' => 5,
            'endpoint' => 'https://api.cal.com/v2/user', // ✅ V2 API (v1 deprecated end of 2025)
            'type' => 'http',
        ],
        'retell' => [
            'interval_seconds' => 30,
            'timeout_seconds' => 5,
            'endpoint' => 'https://api.retellai.com/v2/agent',
            'type' => 'http',
        ],
        'database' => [
            'interval_seconds' => 10,
            'timeout_seconds' => 3,
            'type' => 'query',
            'query' => 'SELECT 1',
        ],
        'redis' => [
            'interval_seconds' => 10,
            'timeout_seconds' => 2,
            'type' => 'ping',
        ],
    ];

    /**
     * Run all health checks
     *
     * @return array Health status of all services
     */
    public function runAllHealthChecks(): array
    {
        $results = [];

        foreach (array_keys(self::HEALTH_PROBES) as $serviceName) {
            $results[$serviceName] = $this->checkServiceHealth($serviceName);
        }

        $this->storeHealthSnapshot($results);
        $this->checkForAlerts($results);

        return $results;
    }

    /**
     * Check health of specific service
     *
     * @param string $serviceName Service to check
     * @return array Health check results
     */
    public function checkServiceHealth(string $serviceName): array
    {
        $probe = self::HEALTH_PROBES[$serviceName] ?? null;

        if (!$probe) {
            return [
                'service' => $serviceName,
                'status' => 'unknown',
                'message' => 'No health probe configured',
            ];
        }

        $startTime = microtime(true);

        try {
            $result = match ($probe['type']) {
                'http' => $this->probeHttp($serviceName, $probe),
                'query' => $this->probeDatabase($serviceName, $probe),
                'ping' => $this->probeRedis($serviceName, $probe),
                default => ['status' => 'unknown', 'message' => 'Unknown probe type'],
            };

            $responseTime = (microtime(true) - $startTime) * 1000;  // ms

            return array_merge($result, [
                'service' => $serviceName,
                'response_time_ms' => round($responseTime, 2),
                'checked_at' => now()->toIso8601String(),
                'threshold_ok' => $responseTime < ($probe['timeout_seconds'] * 1000),
            ]);

        } catch (Exception $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;

            Log::warning("Health check failed", [
                'service' => $serviceName,
                'error' => $e->getMessage(),
            ]);

            return [
                'service' => $serviceName,
                'status' => 'down',
                'message' => $e->getMessage(),
                'response_time_ms' => round($responseTime, 2),
                'checked_at' => now()->toIso8601String(),
                'threshold_ok' => false,
            ];
        }
    }

    /**
     * HTTP health probe
     *
     * @param string $serviceName Service name
     * @param array $probe Probe configuration
     * @return array Probe results
     */
    private function probeHttp(string $serviceName, array $probe): array
    {
        try {
            $response = @file_get_contents($probe['endpoint'], false, stream_context_create([
                'http' => [
                    'timeout' => $probe['timeout_seconds'],
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]));

            if ($response === false) {
                return [
                    'status' => 'down',
                    'message' => 'HTTP request failed',
                ];
            }

            return [
                'status' => 'up',
                'message' => 'HTTP request successful',
            ];

        } catch (Exception $e) {
            return [
                'status' => 'down',
                'message' => 'HTTP probe error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Database health probe
     *
     * @param string $serviceName Service name
     * @param array $probe Probe configuration
     * @return array Probe results
     */
    private function probeDatabase(string $serviceName, array $probe): array
    {
        try {
            $result = \DB::select('SELECT 1');

            if ($result) {
                return [
                    'status' => 'up',
                    'message' => 'Database connection successful',
                ];
            }

            return [
                'status' => 'down',
                'message' => 'Database query returned no results',
            ];

        } catch (Exception $e) {
            return [
                'status' => 'down',
                'message' => 'Database error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Redis health probe
     *
     * @param string $serviceName Service name
     * @param array $probe Probe configuration
     * @return array Probe results
     */
    private function probeRedis(string $serviceName, array $probe): array
    {
        try {
            $redis = Cache::getRedis();
            $response = $redis->ping();

            if ($response) {
                return [
                    'status' => 'up',
                    'message' => 'Redis ping successful',
                ];
            }

            return [
                'status' => 'down',
                'message' => 'Redis ping returned false',
            ];

        } catch (Exception $e) {
            return [
                'status' => 'down',
                'message' => 'Redis error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Store health snapshot for history
     *
     * @param array $results Health check results
     */
    private function storeHealthSnapshot(array $results): void
    {
        try {
            $snapshot = [
                'timestamp' => now()->toIso8601String(),
                'results' => $results,
                'overall_status' => $this->calculateOverallStatus($results),
            ];

            $snapshotKey = 'health:snapshots:' . now()->timestamp;
            Cache::put($snapshotKey, $snapshot, 604800);  // 7 days

        } catch (Exception $e) {
            Log::warning("Failed to store health snapshot", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate overall system status
     *
     * @param array $results Individual service results
     * @return string Overall status
     */
    private function calculateOverallStatus(array $results): string
    {
        $downServices = 0;

        foreach ($results as $result) {
            if ($result['status'] === 'down') {
                $downServices++;
            }
        }

        if ($downServices === 0) {
            return 'healthy';
        } elseif ($downServices === 1) {
            return 'degraded';
        } else {
            return 'critical';
        }
    }

    /**
     * Check for alerts and log issues
     *
     * @param array $results Health check results
     */
    private function checkForAlerts(array $results): void
    {
        foreach ($results as $result) {
            if ($result['status'] === 'down') {
                Log::alert("🚨 Service health check FAILED", [
                    'service' => $result['service'],
                    'message' => $result['message'],
                    'response_time_ms' => $result['response_time_ms'],
                ]);

                // Increment failure counter for circuit breaker awareness
                $failureKey = "health:failures:{$result['service']}";
                Cache::increment($failureKey);

                // Expire failures after 5 minutes
                Cache::put($failureKey, Cache::get($failureKey, 0), 300);
            }

            // Check for slow responses
            if (($result['response_time_ms'] ?? 0) > 1000) {
                Log::warning("⚠️ Service response slow", [
                    'service' => $result['service'],
                    'response_time_ms' => $result['response_time_ms'],
                ]);
            }
        }
    }

    /**
     * Get health history for service
     *
     * @param string $serviceName Service to check
     * @param int $minutes Minutes of history (default: 60)
     * @return array Health history
     */
    public function getHealthHistory(string $serviceName, int $minutes = 60): array
    {
        try {
            $now = now();
            $history = [];

            for ($i = 0; $i < $minutes; $i++) {
                $time = $now->copy()->subMinutes($i);
                $snapshotKey = 'health:snapshots:' . $time->timestamp;
                $snapshot = Cache::get($snapshotKey);

                if ($snapshot && isset($snapshot['results'][$serviceName])) {
                    $history[] = [
                        'timestamp' => $snapshot['timestamp'],
                        'status' => $snapshot['results'][$serviceName]['status'],
                        'response_time_ms' => $snapshot['results'][$serviceName]['response_time_ms'],
                    ];
                }
            }

            return array_reverse($history);

        } catch (Exception $e) {
            Log::warning("Failed to get health history", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get health metrics (uptime, MTTF, MTTR)
     *
     * @param string $serviceName Service to analyze
     * @return array Health metrics
     */
    public function getHealthMetrics(string $serviceName): array
    {
        try {
            $history = $this->getHealthHistory($serviceName, 1440);  // 24 hours

            if (empty($history)) {
                return ['message' => 'Insufficient data for metrics'];
            }

            $upCount = count(array_filter($history, fn($h) => $h['status'] === 'up'));
            $downCount = count($history) - $upCount;
            $uptime = ($upCount / count($history)) * 100;

            $avgResponseTime = count($history) > 0
                ? array_sum(array_column($history, 'response_time_ms')) / count($history)
                : 0;

            return [
                'service' => $serviceName,
                'uptime_percent' => round($uptime, 2),
                'observations' => count($history),
                'up_count' => $upCount,
                'down_count' => $downCount,
                'avg_response_time_ms' => round($avgResponseTime, 2),
                'max_response_time_ms' => max(array_column($history, 'response_time_ms')),
                'min_response_time_ms' => min(array_column($history, 'response_time_ms')),
            ];

        } catch (Exception $e) {
            Log::warning("Failed to calculate health metrics", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get current health status summary
     *
     * @return array Current health status
     */
    public function getCurrentHealthStatus(): array
    {
        try {
            $redis = Cache::getRedis();
            $snapshot = $redis->get('health:current_snapshot');

            if ($snapshot) {
                return json_decode($snapshot, true);
            }

            // Run fresh check
            $results = $this->runAllHealthChecks();

            return [
                'timestamp' => now()->toIso8601String(),
                'overall_status' => $this->calculateOverallStatus($results),
                'services' => $results,
            ];

        } catch (Exception $e) {
            Log::warning("Failed to get current health status", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Predict service health (anomaly detection)
     *
     * Uses recent health patterns to predict issues
     *
     * @param string $serviceName Service to predict for
     * @return array Prediction and recommendations
     */
    public function predictServiceHealth(string $serviceName): array
    {
        try {
            $history = $this->getHealthHistory($serviceName, 60);

            if (count($history) < 10) {
                return ['message' => 'Insufficient data for prediction'];
            }

            // Trend analysis
            $recentHistory = array_slice($history, -10);
            $downCount = count(array_filter($recentHistory, fn($h) => $h['status'] === 'down'));
            $trend = $downCount > 5 ? 'degrading' : 'stable';

            // Response time trend
            $responseTimes = array_column($recentHistory, 'response_time_ms');
            $avgRecent = array_sum($responseTimes) / count($responseTimes);
            $avgOverall = array_sum(array_column($history, 'response_time_ms')) / count($history);
            $speedTrend = $avgRecent > $avgOverall ? 'slowing' : 'stable';

            return [
                'service' => $serviceName,
                'health_trend' => $trend,
                'response_trend' => $speedTrend,
                'recent_downtime' => $downCount,
                'prediction' => $trend === 'degrading' ? 'May fail soon' : 'Expected to remain healthy',
                'recommendations' => $this->getHealthRecommendations($serviceName, $trend, $speedTrend),
            ];

        } catch (Exception $e) {
            Log::warning("Failed to predict health", [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get recommendations based on health trends
     *
     * @param string $serviceName Service name
     * @param string $healthTrend Health trend
     * @param string $speedTrend Speed trend
     * @return array Recommendations
     */
    private function getHealthRecommendations(
        string $serviceName,
        string $healthTrend,
        string $speedTrend
    ): array {
        $recommendations = [];

        if ($healthTrend === 'degrading') {
            $recommendations[] = "Investigate {$serviceName} - failure rate increasing";
            $recommendations[] = "Prepare fallback strategies";
            $recommendations[] = "Monitor circuit breaker status";
        }

        if ($speedTrend === 'slowing') {
            $recommendations[] = "Response time increasing for {$serviceName}";
            $recommendations[] = "Check service resource usage";
            $recommendations[] = "Review recent deployments";
        }

        if (empty($recommendations)) {
            $recommendations[] = "Service operating normally";
        }

        return $recommendations;
    }
}
