<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * QueueHealthCheckCommand
 *
 * Monitors queue health and triggers automatic worker restart on stale job detection.
 * Runs every 5 minutes via scheduler to catch "stale workers" - processes that are
 * running but not processing jobs (memory leak, deadlock, etc.).
 *
 * Detection Logic:
 * - Jobs older than 5 minutes in queue = potential stale worker
 * - Threshold: >10 stale jobs triggers restart
 * - Uses `queue:restart` for graceful restart (finishes current job first)
 *
 * Error Handling:
 * - Database failures: Logs error, continues with defaults
 * - Redis failures: Marks queue as -1 (error indicator)
 * - Caches health status for dashboard visibility
 *
 * @package App\Console\Commands
 */
class QueueHealthCheckCommand extends Command
{
    protected $signature = 'queue:health-check
                            {--threshold=10 : Number of stale jobs before triggering restart}
                            {--stale-minutes=5 : Minutes after which a job is considered stale}
                            {--dry-run : Check only, do not restart workers}';

    protected $description = 'Check queue health and restart workers if stale jobs detected';

    /**
     * Queue names to monitor.
     */
    private const QUEUES = [
        'default',
        'audio-processing',
        'emails',
        'enrichment',
        'cache',
    ];

    /**
     * Cache key for health status (visible in dashboard).
     */
    private const CACHE_KEY = 'queue:health:status';
    private const CACHE_TTL = 600; // 10 minutes

    public function handle(): int
    {
        $startTime = microtime(true);

        try {
            // Validate options
            $threshold = $this->validateThreshold();
            $staleMinutes = $this->validateStaleMinutes();
            $dryRun = (bool) $this->option('dry-run');

            // 1. Check database queue for stale jobs
            $staleJobCount = $this->getStaleJobCount($staleMinutes);

            // 2. Check Redis connectivity and queue depths
            $redisHealthy = $this->checkRedisConnection();
            $queueDepths = $redisHealthy ? $this->getRedisQueueDepths() : $this->getDefaultQueueDepths();
            $totalQueueDepth = array_sum(array_filter($queueDepths, fn($d) => $d >= 0));

            // 3. Check failed jobs in last hour
            $recentFailedJobs = $this->getRecentFailedJobCount();

            // 4. Check restart signal timestamp (prevent restart loops)
            $lastRestartAt = $this->getLastRestartTimestamp();
            $restartCooldown = $lastRestartAt && (time() - $lastRestartAt) < 300; // 5 min cooldown

            // Calculate execution time
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Build health status
            $healthStatus = [
                'status' => 'healthy',
                'stale_jobs' => $staleJobCount,
                'queue_depths' => $queueDepths,
                'total_pending' => $totalQueueDepth,
                'failed_last_hour' => $recentFailedJobs,
                'threshold' => $threshold,
                'redis_healthy' => $redisHealthy,
                'restart_cooldown' => $restartCooldown,
                'last_restart_at' => $lastRestartAt,
                'checked_at' => now()->toIso8601String(),
                'duration_ms' => $durationMs,
            ];

            // Log metrics
            Log::info('[QueueHealth] Health check completed', $healthStatus);

            // Console output
            $this->renderConsoleOutput($healthStatus, $staleMinutes);

            // 5. Trigger restart if stale jobs exceed threshold
            if ($staleJobCount > $threshold) {
                $healthStatus['status'] = 'critical';

                if ($restartCooldown) {
                    Log::warning('[QueueHealth] Restart cooldown active - skipping restart', [
                        'stale_jobs' => $staleJobCount,
                        'last_restart_at' => $lastRestartAt,
                        'cooldown_remaining_sec' => 300 - (time() - $lastRestartAt),
                    ]);

                    $this->newLine();
                    $this->components->warn("⏳ Restart cooldown active (5 min between restarts)");
                } elseif ($dryRun) {
                    $this->components->warn("DRY RUN: Would restart queue workers");
                    Log::warning('[QueueHealth] DRY RUN - would restart workers');
                } else {
                    $this->triggerQueueRestart($staleJobCount, $threshold);
                }

                $this->cacheHealthStatus($healthStatus);
                return self::FAILURE;
            }

            // 6. Warn about high queue depth (but don't restart)
            if ($totalQueueDepth > 100) {
                $healthStatus['status'] = 'warning';
                Log::warning('[QueueHealth] High queue depth detected', [
                    'total_pending' => $totalQueueDepth,
                    'queue_depths' => $queueDepths,
                ]);

                $this->newLine();
                $this->components->warn("⚠️  High queue depth: {$totalQueueDepth} pending jobs");
            }

            // 7. Warn if Redis is unhealthy
            if (!$redisHealthy) {
                $healthStatus['status'] = 'degraded';
                $this->newLine();
                $this->components->warn("⚠️  Redis connection issues detected");
            }

            $this->cacheHealthStatus($healthStatus);

            $this->newLine();
            $this->components->success("✓ Queue health OK ({$durationMs}ms)");

            return self::SUCCESS;

        } catch (\Throwable $e) {
            // Catch-all for unexpected errors
            Log::error('[QueueHealth] Unexpected error during health check', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->components->error("❌ Health check failed: " . $e->getMessage());

            // Cache error status
            $this->cacheHealthStatus([
                'status' => 'error',
                'error' => $e->getMessage(),
                'checked_at' => now()->toIso8601String(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Validate threshold option.
     */
    private function validateThreshold(): int
    {
        $threshold = (int) $this->option('threshold');

        if ($threshold < 0) {
            Log::warning('[QueueHealth] Invalid threshold, using default', [
                'provided' => $this->option('threshold'),
                'using' => 10,
            ]);
            return 10;
        }

        if ($threshold > 1000) {
            Log::warning('[QueueHealth] Threshold too high, capping', [
                'provided' => $threshold,
                'using' => 1000,
            ]);
            return 1000;
        }

        return $threshold;
    }

    /**
     * Validate stale-minutes option.
     */
    private function validateStaleMinutes(): int
    {
        $minutes = (int) $this->option('stale-minutes');

        if ($minutes < 1) {
            Log::warning('[QueueHealth] Invalid stale-minutes, using default', [
                'provided' => $this->option('stale-minutes'),
                'using' => 5,
            ]);
            return 5;
        }

        if ($minutes > 60) {
            Log::warning('[QueueHealth] stale-minutes too high, capping', [
                'provided' => $minutes,
                'using' => 60,
            ]);
            return 60;
        }

        return $minutes;
    }

    /**
     * Check Redis connection health.
     */
    private function checkRedisConnection(): bool
    {
        try {
            $pong = Redis::ping();
            return $pong === true || $pong === 'PONG' || $pong === '+PONG';
        } catch (\Exception $e) {
            Log::error('[QueueHealth] Redis connection failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get count of stale jobs (older than X minutes).
     */
    private function getStaleJobCount(int $minutes): int
    {
        try {
            $threshold = now()->subMinutes($minutes)->timestamp;

            return (int) DB::table('jobs')
                ->where('available_at', '<', $threshold)
                ->count();
        } catch (\Exception $e) {
            Log::error('[QueueHealth] Failed to query stale jobs', [
                'error' => $e->getMessage(),
            ]);
            return -1; // Indicate error
        }
    }

    /**
     * Get current depth of each Redis queue.
     */
    private function getRedisQueueDepths(): array
    {
        $depths = [];

        foreach (self::QUEUES as $queue) {
            try {
                $key = "queues:{$queue}";
                $depth = Redis::llen($key) ?? 0;
                $depths[$queue] = (int) $depth;
            } catch (\Exception $e) {
                Log::warning('[QueueHealth] Failed to get Redis queue depth', [
                    'queue' => $queue,
                    'error' => $e->getMessage(),
                ]);
                $depths[$queue] = -1;
            }
        }

        return $depths;
    }

    /**
     * Get default queue depths (when Redis unavailable).
     */
    private function getDefaultQueueDepths(): array
    {
        $depths = [];
        foreach (self::QUEUES as $queue) {
            $depths[$queue] = -1; // Unknown
        }
        return $depths;
    }

    /**
     * Get count of failed jobs in the last hour.
     */
    private function getRecentFailedJobCount(): int
    {
        try {
            return (int) DB::table('failed_jobs')
                ->where('failed_at', '>', now()->subHour())
                ->count();
        } catch (\Exception $e) {
            Log::error('[QueueHealth] Failed to query failed jobs', [
                'error' => $e->getMessage(),
            ]);
            return -1;
        }
    }

    /**
     * Get timestamp of last queue restart (from cache).
     */
    private function getLastRestartTimestamp(): ?int
    {
        try {
            $timestamp = Cache::get('queue:last_restart_at');
            return $timestamp !== null ? (int) $timestamp : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Trigger queue restart with logging.
     */
    private function triggerQueueRestart(int $staleJobCount, int $threshold): void
    {
        Log::critical('[QueueHealth] Stale jobs threshold exceeded - triggering restart', [
            'stale_jobs' => $staleJobCount,
            'threshold' => $threshold,
        ]);

        $this->newLine();
        $this->components->error("⚠️  Stale jobs detected: {$staleJobCount} > threshold {$threshold}");

        try {
            // Record restart timestamp (for cooldown)
            Cache::put('queue:last_restart_at', time(), 3600);

            // queue:restart sends SIGTERM signal to all workers
            Artisan::call('queue:restart');

            $this->components->info("✓ Queue restart signal sent (workers will restart gracefully)");
            Log::warning('[QueueHealth] Queue restart signal sent', [
                'stale_jobs' => $staleJobCount,
            ]);
        } catch (\Exception $e) {
            Log::error('[QueueHealth] Failed to trigger queue restart', [
                'error' => $e->getMessage(),
            ]);
            $this->components->error("❌ Failed to send restart signal: " . $e->getMessage());
        }
    }

    /**
     * Cache health status for dashboard visibility.
     */
    private function cacheHealthStatus(array $status): void
    {
        try {
            Cache::put(self::CACHE_KEY, $status, self::CACHE_TTL);
        } catch (\Exception $e) {
            // Silent fail - caching is optional
            Log::debug('[QueueHealth] Failed to cache status', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Render console output.
     */
    private function renderConsoleOutput(array $status, int $staleMinutes): void
    {
        $this->components->info("Queue Health Check");
        $this->newLine();

        $staleLabel = $status['stale_jobs'] >= 0 ? (string) $status['stale_jobs'] : 'ERROR';
        $this->components->twoColumnDetail("Stale Jobs (>{$staleMinutes} min)", $staleLabel);
        $this->components->twoColumnDetail('Total Pending Jobs', (string) $status['total_pending']);

        $failedLabel = $status['failed_last_hour'] >= 0 ? (string) $status['failed_last_hour'] : 'ERROR';
        $this->components->twoColumnDetail('Failed Jobs (last hour)', $failedLabel);

        $redisStatus = $status['redis_healthy'] ? '✓ Connected' : '⚠️ Disconnected';
        $this->components->twoColumnDetail('Redis Status', $redisStatus);

        $this->newLine();
        $this->components->info("Queue Depths:");

        foreach ($status['queue_depths'] as $queue => $depth) {
            if ($depth < 0) {
                $this->components->twoColumnDetail("  {$queue}", "⚠️ ERROR");
            } elseif ($depth > 50) {
                $this->components->twoColumnDetail("  {$queue}", "⚠️ {$depth}");
            } else {
                $this->components->twoColumnDetail("  {$queue}", "✓ {$depth}");
            }
        }
    }
}
