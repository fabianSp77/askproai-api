<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Stripe\Stripe;
use Stripe\Exception\ApiConnectionException;

class HealthCheckService
{
    private array $results = [];
    private bool $healthy = true;
    private array $config;

    public function __construct()
    {
        $this->config = config('monitoring.health_checks');
    }

    /**
     * Run all health checks
     */
    public function check(): array
    {
        if (!$this->config['enabled']) {
            return ['status' => 'disabled'];
        }

        // Run critical checks
        foreach ($this->config['checks']['critical'] as $name => $config) {
            if ($config['enabled'] ?? true) {
                $this->runCheck($name, $config, true);
            }
        }

        // Run warning checks
        foreach ($this->config['checks']['warning'] as $name => $config) {
            if ($config['enabled'] ?? true) {
                $this->runCheck($name, $config, false);
            }
        }

        return [
            'status' => $this->healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $this->results,
        ];
    }

    /**
     * Run a specific health check
     */
    private function runCheck(string $name, array $config, bool $critical): void
    {
        $startTime = microtime(true);
        $result = ['name' => $name, 'critical' => $critical];

        try {
            switch ($name) {
                case 'database':
                    $result = array_merge($result, $this->checkDatabase($config));
                    break;
                case 'redis':
                    $result = array_merge($result, $this->checkRedis($config));
                    break;
                case 'stripe_api':
                    $result = array_merge($result, $this->checkStripeApi($config));
                    break;
                case 'queue_size':
                    $result = array_merge($result, $this->checkQueueSize($config));
                    break;
                case 'disk_space':
                    $result = array_merge($result, $this->checkDiskSpace($config));
                    break;
                case 'memory_usage':
                    $result = array_merge($result, $this->checkMemoryUsage($config));
                    break;
                default:
                    $result = array_merge($result, [
                        'status' => 'unknown',
                        'message' => 'Unknown check type',
                    ]);
            }
        } catch (\Exception $e) {
            $result = array_merge($result, [
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }

        $result['duration'] = round((microtime(true) - $startTime) * 1000, 2);
        
        if ($critical && $result['status'] !== 'ok') {
            $this->healthy = false;
        }

        $this->results[] = $result;
    }

    /**
     * Check database connection
     */
    private function checkDatabase(array $config): array
    {
        try {
            DB::connection()->getPdo();
            
            // Check if we can execute a simple query
            $result = DB::select('SELECT 1 as health_check');
            
            if (!empty($result)) {
                return [
                    'status' => 'ok',
                    'message' => 'Database connection is healthy',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Database check failed',
        ];
    }

    /**
     * Check Redis connection
     */
    private function checkRedis(array $config): array
    {
        try {
            $redis = Redis::connection();
            $redis->ping();
            
            // Test write and read
            $testKey = 'health_check_' . time();
            $testValue = 'ok';
            
            $redis->setex($testKey, 10, $testValue);
            $retrieved = $redis->get($testKey);
            $redis->del($testKey);
            
            if ($retrieved === $testValue) {
                return [
                    'status' => 'ok',
                    'message' => 'Redis connection is healthy',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Redis connection failed: ' . $e->getMessage(),
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Redis check failed',
        ];
    }

    /**
     * Check Stripe API connection
     */
    private function checkStripeApi(array $config): array
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            
            // Try to retrieve balance as a simple API check
            $balance = \Stripe\Balance::retrieve();
            
            if ($balance && isset($balance->available)) {
                return [
                    'status' => 'ok',
                    'message' => 'Stripe API connection is healthy',
                    'meta' => [
                        'mode' => config('services.stripe.mode', 'test'),
                    ],
                ];
            }
        } catch (ApiConnectionException $e) {
            return [
                'status' => 'error',
                'message' => 'Stripe API connection failed: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Stripe API check failed: ' . $e->getMessage(),
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Stripe API check failed',
        ];
    }

    /**
     * Check queue size
     */
    private function checkQueueSize(array $config): array
    {
        try {
            $queues = ['default', 'webhooks', 'stripe', 'emails'];
            $totalSize = 0;
            $queueSizes = [];

            foreach ($queues as $queue) {
                $size = Redis::connection()->llen("queue:$queue");
                $queueSizes[$queue] = $size;
                $totalSize += $size;
            }

            $threshold = $config['threshold'] ?? 1000;
            
            if ($totalSize > $threshold) {
                return [
                    'status' => 'warning',
                    'message' => "Queue size ($totalSize) exceeds threshold ($threshold)",
                    'meta' => [
                        'total_size' => $totalSize,
                        'threshold' => $threshold,
                        'queues' => $queueSizes,
                    ],
                ];
            }

            return [
                'status' => 'ok',
                'message' => 'Queue sizes are within limits',
                'meta' => [
                    'total_size' => $totalSize,
                    'queues' => $queueSizes,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Queue size check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check disk space
     */
    private function checkDiskSpace(array $config): array
    {
        try {
            $path = base_path();
            $totalSpace = disk_total_space($path);
            $freeSpace = disk_free_space($path);
            $usedPercentage = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);
            
            $threshold = $config['threshold'] ?? 90;
            
            if ($usedPercentage > $threshold) {
                return [
                    'status' => 'warning',
                    'message' => "Disk usage ($usedPercentage%) exceeds threshold ($threshold%)",
                    'meta' => [
                        'used_percentage' => $usedPercentage,
                        'free_space_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                        'total_space_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                    ],
                ];
            }

            return [
                'status' => 'ok',
                'message' => 'Disk space is adequate',
                'meta' => [
                    'used_percentage' => $usedPercentage,
                    'free_space_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Disk space check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check memory usage
     */
    private function checkMemoryUsage(array $config): array
    {
        try {
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = $this->getMemoryLimitInBytes();
            $usedPercentage = round(($memoryUsage / $memoryLimit) * 100, 2);
            
            $threshold = $config['threshold'] ?? 85;
            
            if ($usedPercentage > $threshold) {
                return [
                    'status' => 'warning',
                    'message' => "Memory usage ($usedPercentage%) exceeds threshold ($threshold%)",
                    'meta' => [
                        'used_percentage' => $usedPercentage,
                        'used_mb' => round($memoryUsage / 1024 / 1024, 2),
                        'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
                    ],
                ];
            }

            return [
                'status' => 'ok',
                'message' => 'Memory usage is within limits',
                'meta' => [
                    'used_percentage' => $usedPercentage,
                    'used_mb' => round($memoryUsage / 1024 / 1024, 2),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Memory usage check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get memory limit in bytes
     */
    private function getMemoryLimitInBytes(): int
    {
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit == -1) {
            // Unlimited, return a large number
            return PHP_INT_MAX;
        }

        preg_match('/^(\d+)(.)$/', $memoryLimit, $matches);
        if (!$matches) {
            return PHP_INT_MAX;
        }

        $value = (int) $matches[1];
        $unit = strtolower($matches[2]);

        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return $value;
        }
    }
}