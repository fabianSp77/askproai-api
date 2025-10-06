<?php

namespace App\Http\Middleware;

use App\Debug\MemoryDumper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Production-safe memory profiler with sampling and safety controls
 */
class ProductionMemoryProfiler
{
    private const SAMPLE_RATE = 0.01; // Profile 1% of requests
    private const FORCE_HEADER = 'X-Force-Memory-Profile';
    private const CRITICAL_THRESHOLD = 1792 * 1024 * 1024; // 1.75GB

    public function handle(Request $request, Closure $next)
    {
        $shouldProfile = $this->shouldProfile($request);

        if ($shouldProfile) {
            $this->startProfiling($request);
        }

        // Always check for critical memory
        $this->registerCriticalCheck();

        $response = $next($request);

        if ($shouldProfile) {
            $this->endProfiling($request);
        }

        return $response;
    }

    private function shouldProfile(Request $request): bool
    {
        // Force profiling with header (for controlled testing)
        if ($request->hasHeader(self::FORCE_HEADER)) {
            return true;
        }

        // Sample random requests
        if (config('app.memory_profile_enabled', false)) {
            return mt_rand(1, 100) <= (self::SAMPLE_RATE * 100);
        }

        return false;
    }

    private function startProfiling(Request $request): void
    {
        // Record initial state
        $request->attributes->set('memory_profile_start', [
            'time' => microtime(true),
            'memory' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'queries' => 0, // Will be tracked separately
        ]);
    }

    private function endProfiling(Request $request): void
    {
        $start = $request->attributes->get('memory_profile_start');

        if (!$start) {
            return;
        }

        $end = [
            'time' => microtime(true),
            'memory' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
        ];

        $duration = ($end['time'] - $start['time']) * 1000; // ms
        $memoryGrowth = $end['memory'] - $start['memory'];
        $peakUsage = $end['peak'];

        // Log if interesting (high memory or large growth)
        if ($peakUsage > 1536 * 1024 * 1024 || $memoryGrowth > 256 * 1024 * 1024) {
            Log::info('Memory profile sample', [
                'url' => $request->url(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
                'duration_ms' => round($duration, 2),
                'memory_growth_mb' => round($memoryGrowth / 1024 / 1024, 2),
                'peak_mb' => round($peakUsage / 1024 / 1024, 2),
                'start_mb' => round($start['memory'] / 1024 / 1024, 2),
                'end_mb' => round($end['memory'] / 1024 / 1024, 2),
            ]);
        }
    }

    private function registerCriticalCheck(): void
    {
        // Register a tick function to check memory every 100 ticks
        // This catches OOM before it happens
        static $tickCount = 0;

        register_tick_function(function () use (&$tickCount) {
            $tickCount++;

            if ($tickCount % 100 === 0) {
                $current = memory_get_usage(true);

                if ($current > self::CRITICAL_THRESHOLD) {
                    // Dump and log immediately
                    MemoryDumper::dumpIfCritical('critical_threshold_reached');

                    // Optionally throw exception to fail gracefully
                    // throw new \RuntimeException('Critical memory threshold reached');
                }
            }
        });
    }
}
