<?php

namespace App\Services\Resilience;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Circuit Breaker State Manager - Centralized coordination
 *
 * Manages multiple circuit breakers across the system:
 * - Coordinated state transitions
 * - Cross-service dependencies (e.g., if Cal.com fails, Retell fails)
 * - Global state snapshots
 * - Cascading failure detection
 *
 * Architecture:
 * - Each service has its own circuit breaker (independent)
 * - State Manager provides coordination (dependent)
 * - Cascade rules: Cal.com down → Retell down → Appointment creation fails
 */
class CircuitBreakerStateManager
{
    /**
     * Known services with circuit breakers
     */
    private const SERVICES = [
        'calcom' => 'Cal.com API',
        'retell' => 'Retell AI',
        'database' => 'PostgreSQL Database',
        'redis' => 'Redis Cache',
        'webhooks' => 'Webhook Processing',
    ];

    /**
     * Service dependencies (upstream services must be healthy)
     */
    private const DEPENDENCIES = [
        'retell' => ['calcom'],           // Retell depends on Cal.com
        'appointments' => ['calcom', 'retell'], // Appointments depend on both
        'webhooks' => ['database'],        // Webhooks depend on DB
    ];

    /**
     * Get all circuit breaker states
     *
     * @return array State of all services
     */
    public function getAllStates(): array
    {
        $states = [];

        foreach (array_keys(self::SERVICES) as $serviceName) {
            $breaker = new DistributedCircuitBreaker($serviceName);
            $states[$serviceName] = $breaker->getStatus();
        }

        return $states;
    }

    /**
     * Get system health status
     *
     * Evaluates overall system health based on circuit breaker states
     *
     * @return array Health status with degradation info
     */
    public function getSystemHealth(): array
    {
        $states = $this->getAllStates();
        $openCircuits = [];
        $halfOpenCircuits = [];
        $closedCircuits = [];

        foreach ($states as $service => $status) {
            $state = $status['state'];
            if ($state === 'open') {
                $openCircuits[] = $service;
            } elseif ($state === 'half_open') {
                $halfOpenCircuits[] = $service;
            } else {
                $closedCircuits[] = $service;
            }
        }

        // Determine system health
        if (count($openCircuits) > 0) {
            $health = 'degraded';
            $severity = 'warning';
            if (count($openCircuits) >= 2) {
                $health = 'critical';
                $severity = 'error';
            }
        } elseif (count($halfOpenCircuits) > 0) {
            $health = 'recovering';
            $severity = 'info';
        } else {
            $health = 'healthy';
            $severity = 'info';
        }

        $cascadingFailures = $this->detectCascadingFailures($openCircuits);

        return [
            'timestamp' => now()->toIso8601String(),
            'health' => $health,
            'severity' => $severity,
            'open_circuits' => $openCircuits,
            'half_open_circuits' => $halfOpenCircuits,
            'closed_circuits' => $closedCircuits,
            'cascading_failures' => $cascadingFailures,
            'services' => $states,
        ];
    }

    /**
     * Detect cascading failures
     *
     * If upstream service fails, dependent services will also fail
     * Example: Cal.com down → Retell fails → Appointments fail
     *
     * @param array $openCircuits Services with open circuits
     * @return array Detected cascading failures
     */
    private function detectCascadingFailures(array $openCircuits): array
    {
        $cascades = [];

        foreach (self::DEPENDENCIES as $service => $dependencies) {
            $failedDeps = array_intersect($dependencies, $openCircuits);

            if (count($failedDeps) > 0) {
                $cascades[] = [
                    'service' => $service,
                    'depends_on' => $dependencies,
                    'failed_dependencies' => $failedDeps,
                    'will_fail' => true,
                    'reason' => "{$service} depends on: " . implode(', ', $failedDeps),
                ];
            }
        }

        return $cascades;
    }

    /**
     * Check if a service is healthy (can accept requests)
     *
     * Returns false if:
     * - Service's own circuit is open
     * - Any upstream dependency is open
     *
     * @param string $serviceName Service to check
     * @return bool True if service can accept requests
     */
    public function isServiceHealthy(string $serviceName): bool
    {
        $breaker = new DistributedCircuitBreaker($serviceName);
        $status = $breaker->getStatus();

        if ($status['state'] === 'open') {
            return false;
        }

        // Check dependencies
        if (isset(self::DEPENDENCIES[$serviceName])) {
            foreach (self::DEPENDENCIES[$serviceName] as $dependency) {
                $depBreaker = new DistributedCircuitBreaker($dependency);
                $depStatus = $depBreaker->getStatus();
                if ($depStatus['state'] === 'open') {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get service health reason
     *
     * Explains why service is healthy or degraded
     *
     * @param string $serviceName Service name
     * @return array Health reason
     */
    public function getServiceHealthReason(string $serviceName): array
    {
        $breaker = new DistributedCircuitBreaker($serviceName);
        $status = $breaker->getStatus();
        $reasons = [];

        if ($status['state'] === 'open') {
            $reasons[] = "Circuit is OPEN (failures: {$status['failures']})";
        } elseif ($status['state'] === 'half_open') {
            $reasons[] = "Circuit is HALF_OPEN (testing recovery)";
        }

        // Check dependencies
        if (isset(self::DEPENDENCIES[$serviceName])) {
            foreach (self::DEPENDENCIES[$serviceName] as $dependency) {
                $depBreaker = new DistributedCircuitBreaker($dependency);
                $depStatus = $depBreaker->getStatus();
                if ($depStatus['state'] === 'open') {
                    $reasons[] = "Dependency '{$dependency}' circuit is OPEN";
                }
            }
        }

        return [
            'service' => $serviceName,
            'is_healthy' => empty($reasons),
            'state' => $status['state'],
            'reasons' => $reasons,
        ];
    }

    /**
     * Force reset all circuit breakers (emergency/admin only)
     *
     * Useful after infrastructure maintenance
     *
     * @return array Reset results
     */
    public function forceResetAll(): array
    {
        $results = [];

        foreach (array_keys(self::SERVICES) as $serviceName) {
            try {
                $breaker = new DistributedCircuitBreaker($serviceName);
                $breaker->reset();
                $results[$serviceName] = 'reset';
                Log::warning("🔄 Circuit breaker manually reset: {$serviceName}");
            } catch (Exception $e) {
                $results[$serviceName] = "error: {$e->getMessage()}";
                Log::error("Failed to reset circuit breaker: {$serviceName}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Reset specific service circuit breaker
     *
     * @param string $serviceName Service to reset
     * @return bool Success
     */
    public function resetService(string $serviceName): bool
    {
        try {
            if (!isset(self::SERVICES[$serviceName])) {
                throw new Exception("Unknown service: {$serviceName}");
            }

            $breaker = new DistributedCircuitBreaker($serviceName);
            $breaker->reset();
            Log::info("🔄 Circuit breaker reset: {$serviceName}");
            return true;

        } catch (Exception $e) {
            Log::error("Failed to reset circuit breaker", [
                'service' => $serviceName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Store state snapshot for analysis/debugging
     *
     * Useful for debugging cascading failures
     *
     * @return array Snapshot results
     */
    public function captureStateSnapshot(): array
    {
        $snapshot = [
            'timestamp' => now()->toIso8601String(),
            'health' => $this->getSystemHealth(),
            'services' => $this->getAllStates(),
        ];

        $snapshotKey = 'circuit_breaker:snapshots:' . now()->timestamp;
        Cache::put($snapshotKey, $snapshot, 604800);  // Keep 7 days

        Log::info('📸 State snapshot captured', [
            'key' => $snapshotKey,
            'health' => $snapshot['health']['health'],
        ]);

        return $snapshot;
    }

    /**
     * Get recent state snapshots
     *
     * @param int $count Number of snapshots to retrieve
     * @return array Recent snapshots
     */
    public function getRecentSnapshots(int $count = 10): array
    {
        $snapshots = [];

        try {
            $redis = Cache::getRedis();
            $pattern = 'circuit_breaker:snapshots:*';

            // Get matching keys
            $cursor = 0;
            $keys = [];

            do {
                $results = $redis->scan($cursor, 'MATCH', $pattern, 'COUNT', 100);
                $cursor = $results[0];
                $keys = array_merge($keys, $results[1]);
            } while ($cursor !== 0);

            // Get most recent snapshots
            rsort($keys);
            $recentKeys = array_slice($keys, 0, $count);

            foreach ($recentKeys as $key) {
                $snapshot = Cache::get($key);
                if ($snapshot) {
                    $snapshots[] = $snapshot;
                }
            }

        } catch (Exception $e) {
            Log::warning('Failed to retrieve snapshots', ['error' => $e->getMessage()]);
        }

        return $snapshots;
    }

    /**
     * Analyze state history for patterns
     *
     * Detect if failures are recurring (e.g., always Cal.com at 3 PM)
     *
     * @return array Pattern analysis
     */
    public function analyzeStatePatterns(): array
    {
        $snapshots = $this->getRecentSnapshots(100);

        if (empty($snapshots)) {
            return ['message' => 'Insufficient data for pattern analysis'];
        }

        $failurePatterns = [];
        $serviceFailures = [];

        foreach ($snapshots as $snapshot) {
            $openCircuits = $snapshot['health']['open_circuits'] ?? [];

            foreach ($openCircuits as $service) {
                if (!isset($serviceFailures[$service])) {
                    $serviceFailures[$service] = [];
                }
                $serviceFailures[$service][] = $snapshot['timestamp'];
            }
        }

        // Analyze failure frequency
        foreach ($serviceFailures as $service => $timestamps) {
            $frequency = count($timestamps);
            $failureRate = ($frequency / count($snapshots)) * 100;

            $failurePatterns[$service] = [
                'total_snapshots' => count($snapshots),
                'failures' => $frequency,
                'failure_rate' => round($failureRate, 2) . '%',
                'first_failure' => $timestamps[0] ?? null,
                'last_failure' => end($timestamps),
                'concerning' => $failureRate > 20, // >20% = concerning
            ];
        }

        return [
            'analysis_period' => count($snapshots) . ' snapshots',
            'patterns' => $failurePatterns,
            'recommendations' => $this->generateRecommendations($failurePatterns),
        ];
    }

    /**
     * Generate recommendations based on patterns
     *
     * @param array $failurePatterns Service failure patterns
     * @return array Actionable recommendations
     */
    private function generateRecommendations(array $failurePatterns): array
    {
        $recommendations = [];

        foreach ($failurePatterns as $service => $pattern) {
            if ($pattern['concerning']) {
                $recommendations[] = [
                    'service' => $service,
                    'action' => "Investigate {$service} - failure rate {$pattern['failure_rate']}",
                    'severity' => 'warning',
                ];
            }
        }

        if (count($recommendations) === 0) {
            $recommendations[] = [
                'message' => 'All services operating normally',
                'severity' => 'info',
            ];
        }

        return $recommendations;
    }

    /**
     * Get status for monitoring dashboard
     *
     * @return array Dashboard data
     */
    public function getDashboardStatus(): array
    {
        $health = $this->getSystemHealth();

        return [
            'timestamp' => now()->toIso8601String(),
            'overall_health' => $health['health'],
            'severity' => $health['severity'],
            'services' => array_map(function ($service, $status) {
                return [
                    'name' => $service,
                    'state' => $status['state'],
                    'failures' => $status['failures'],
                    'successes' => $status['successes'],
                    'half_open_quota' => $status['half_open_quota'],
                ];
            }, array_keys($health['services']), array_values($health['services'])),
            'open_circuits' => count($health['open_circuits']),
            'degraded_circuits' => count($health['half_open_circuits']),
            'healthy_circuits' => count($health['closed_circuits']),
            'cascading_failures' => $health['cascading_failures'],
        ];
    }
}
