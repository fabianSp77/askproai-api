<?php

namespace App\Debug;

use Illuminate\Support\Facades\Log;

class MemoryDumper
{
    /**
     * Dump detailed memory state when approaching OOM
     */
    public static function dumpIfCritical(string $context = 'unknown'): void
    {
        $current = memory_get_usage(true);
        $limit = self::getMemoryLimit();
        $usage = ($current / $limit) * 100;

        // Dump when >90% memory used
        if ($usage > 90) {
            self::dumpMemoryState($context, $current, $limit);
        }
    }

    /**
     * Force dump of current memory state
     */
    public static function dump(string $context = 'forced'): array
    {
        $current = memory_get_usage(true);
        $limit = self::getMemoryLimit();

        return self::dumpMemoryState($context, $current, $limit);
    }

    private static function dumpMemoryState(string $context, int $current, int $limit): array
    {
        $data = [
            'context' => $context,
            'timestamp' => now()->toISOString(),
            'memory' => [
                'current_mb' => round($current / 1024 / 1024, 2),
                'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'limit_mb' => round($limit / 1024 / 1024, 2),
                'usage_percent' => round(($current / $limit) * 100, 2),
            ],
            'objects' => self::getObjectCounts(),
            'classes' => [
                'declared' => count(get_declared_classes()),
                'interfaces' => count(get_declared_interfaces()),
                'traits' => count(get_declared_traits()),
            ],
            'resources' => self::getResourceInfo(),
            'backtrace' => self::getCompactBacktrace(),
            'session' => self::getSessionInfo(),
            'cache' => self::getCacheInfo(),
        ];

        // Log to dedicated memory debug file
        Log::channel('stack')->critical('Memory state dump', $data);

        // Also save to JSON file for external analysis
        $filename = storage_path('logs/memory-dump-' . time() . '.json');
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));

        return $data;
    }

    private static function getObjectCounts(): array
    {
        if (!function_exists('gc_mem_caches')) {
            return ['error' => 'gc_mem_caches not available'];
        }

        // Force garbage collection to get accurate counts
        gc_collect_cycles();

        $objects = [];
        $totalObjects = 0;

        // Use debug_backtrace to estimate object counts
        // This is imperfect but works without extensions
        foreach (get_declared_classes() as $class) {
            // Skip internal classes
            if (strpos($class, 'Illuminate\\') === 0 ||
                strpos($class, 'Symfony\\') === 0 ||
                strpos($class, 'App\\Models\\') === 0) {
                continue;
            }
        }

        return [
            'total_estimated' => $totalObjects,
            'note' => 'Install php-meminfo for accurate object counting',
        ];
    }

    private static function getResourceInfo(): array
    {
        return [
            'included_files' => count(get_included_files()),
            'included_size_mb' => round(
                array_sum(array_map('filesize', get_included_files())) / 1024 / 1024,
                2
            ),
        ];
    }

    private static function getCompactBacktrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);

        return array_map(function ($frame) {
            return [
                'file' => basename($frame['file'] ?? 'unknown'),
                'line' => $frame['line'] ?? 0,
                'function' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . $frame['function'],
            ];
        }, $trace);
    }

    private static function getSessionInfo(): array
    {
        if (!session()->isStarted()) {
            return ['status' => 'not_started'];
        }

        $data = session()->all();
        $serialized = serialize($data);

        return [
            'size_mb' => round(strlen($serialized) / 1024 / 1024, 2),
            'keys' => array_keys($data),
            'key_count' => count($data),
            'largest_keys' => self::findLargestSessionKeys($data),
        ];
    }

    private static function getCacheInfo(): array
    {
        // Try to estimate cache driver memory usage
        try {
            $driver = config('cache.default');

            return [
                'driver' => $driver,
                'note' => 'Cache memory usage varies by driver',
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private static function findLargestSessionKeys(array $data, int $limit = 5): array
    {
        $sizes = [];

        foreach ($data as $key => $value) {
            $sizes[$key] = strlen(serialize($value));
        }

        arsort($sizes);

        return array_map(
            fn($size) => round($size / 1024, 2) . ' KB',
            array_slice($sizes, 0, $limit, true)
        );
    }

    private static function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');

        if ($limit == -1) {
            return PHP_INT_MAX;
        }

        return self::convertToBytes($limit);
    }

    private static function convertToBytes(string $value): int
    {
        $value = trim($value);
        $unit = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;

        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Register shutdown handler to capture OOM errors
     */
    public static function registerShutdownHandler(): void
    {
        register_shutdown_function(function () {
            $error = error_get_last();

            if ($error && strpos($error['message'], 'Allowed memory size') !== false) {
                // Memory exhausted - dump what we can
                Log::critical('OOM Shutdown Handler', [
                    'error' => $error,
                    'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                    'limit' => ini_get('memory_limit'),
                ]);

                // Try to dump minimal state (might fail if truly OOM)
                try {
                    self::dump('oom_shutdown');
                } catch (\Throwable $e) {
                    Log::critical('Failed to dump memory state during OOM', [
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        });
    }
}
