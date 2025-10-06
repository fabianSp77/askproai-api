<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MemoryCheckpoint
{
    private static array $checkpoints = [];
    private static int $threshold = 1536 * 1024 * 1024; // 1.5GB warning threshold

    public function handle(Request $request, Closure $next)
    {
        // Initial checkpoint
        $this->checkpoint('request_start', [
            'uri' => $request->getRequestUri(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
        ]);

        // Before service providers boot
        $this->checkpoint('before_providers');

        $response = $next($request);

        // After response built
        $this->checkpoint('response_ready');

        return $response;
    }

    public function terminate($request, $response)
    {
        $this->checkpoint('request_end');

        $peak = memory_get_peak_usage(true);

        // Log detailed memory progression if high usage
        if ($peak > self::$threshold) {
            Log::warning('High memory usage detected', [
                'peak_mb' => round($peak / 1024 / 1024, 2),
                'checkpoints' => self::$checkpoints,
                'largest_jump' => $this->findLargestJump(),
                'session_size' => $this->getSessionSize(),
                'loaded_classes' => count(get_declared_classes()),
                'included_files' => count(get_included_files()),
            ]);
        }

        self::$checkpoints = [];
    }

    private function checkpoint(string $label, array $extra = []): void
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        $previous = end(self::$checkpoints);
        $delta = $previous ? $current - $previous['current'] : 0;

        self::$checkpoints[] = [
            'label' => $label,
            'current_mb' => round($current / 1024 / 1024, 2),
            'peak_mb' => round($peak / 1024 / 1024, 2),
            'delta_mb' => round($delta / 1024 / 1024, 2),
            'timestamp' => microtime(true),
            ...$extra,
        ];
    }

    private function findLargestJump(): array
    {
        $largest = ['delta_mb' => 0];

        foreach (self::$checkpoints as $checkpoint) {
            if ($checkpoint['delta_mb'] > $largest['delta_mb']) {
                $largest = $checkpoint;
            }
        }

        return $largest;
    }

    private function getSessionSize(): float
    {
        if (!session()->isStarted()) {
            return 0;
        }

        $sessionData = session()->all();
        return round(strlen(serialize($sessionData)) / 1024 / 1024, 2);
    }
}
