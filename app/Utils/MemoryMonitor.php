<?php

namespace App\Utils;

use Illuminate\Support\Facades\Log;

/**
 * Memory monitoring utility to track repository performance
 */
class MemoryMonitor
{
    private static array $checkpoints = [];
    
    /**
     * Start monitoring a repository operation
     */
    public static function startOperation(string $repository, string $method, array $context = []): string
    {
        $operationId = uniqid($repository . '_' . $method . '_', true);
        
        self::$checkpoints[$operationId] = [
            'repository' => $repository,
            'method' => $method,
            'context' => $context,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(),
            'start_peak_memory' => memory_get_peak_usage(),
            'checkpoints' => []
        ];
        
        return $operationId;
    }
    
    /**
     * Add a checkpoint during operation
     */
    public static function checkpoint(string $operationId, string $description): void
    {
        if (!isset(self::$checkpoints[$operationId])) {
            return;
        }
        
        $currentTime = microtime(true);
        $currentMemory = memory_get_usage();
        $currentPeak = memory_get_peak_usage();
        $operation = &self::$checkpoints[$operationId];
        
        $operation['checkpoints'][] = [
            'description' => $description,
            'time' => $currentTime,
            'memory' => $currentMemory,
            'peak_memory' => $currentPeak,
            'elapsed_time' => $currentTime - $operation['start_time'],
            'memory_delta' => $currentMemory - $operation['start_memory'],
            'peak_delta' => $currentPeak - $operation['start_peak_memory']
        ];
    }
    
    /**
     * Finish monitoring and log results
     */
    public static function endOperation(string $operationId, array $additionalContext = []): array
    {
        if (!isset(self::$checkpoints[$operationId])) {
            return [];
        }
        
        $operation = self::$checkpoints[$operationId];
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $endPeak = memory_get_peak_usage();
        
        $totalTime = $endTime - $operation['start_time'];
        $totalMemoryDelta = $endMemory - $operation['start_memory'];
        $totalPeakDelta = $endPeak - $operation['start_peak_memory'];
        
        $stats = [
            'repository' => $operation['repository'],
            'method' => $operation['method'],
            'context' => array_merge($operation['context'], $additionalContext),
            'total_time_ms' => round($totalTime * 1000, 2),
            'memory_used_mb' => round($totalMemoryDelta / 1024 / 1024, 2),
            'peak_memory_mb' => round($endPeak / 1024 / 1024, 2),
            'peak_delta_mb' => round($totalPeakDelta / 1024 / 1024, 2),
            'checkpoints_count' => count($operation['checkpoints']),
            'checkpoints' => $operation['checkpoints']
        ];
        
        // Log warning if memory usage is high
        if ($totalMemoryDelta > 50 * 1024 * 1024) { // 50MB
            Log::warning('High memory usage detected in repository operation', $stats);
        } elseif (config('app.debug')) {
            Log::debug('Repository operation completed', $stats);
        }
        
        // Clean up
        unset(self::$checkpoints[$operationId]);
        
        return $stats;
    }
    
    /**
     * Get current memory usage formatted
     */
    public static function getCurrentMemoryUsage(): array
    {
        return [
            'current_mb' => round(memory_get_usage() / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
            'limit_mb' => round(self::getMemoryLimit() / 1024 / 1024, 2),
            'usage_percentage' => round((memory_get_usage() / self::getMemoryLimit()) * 100, 2)
        ];
    }
    
    /**
     * Check if memory usage is approaching limit
     */
    public static function isMemoryUsageHigh(float $thresholdPercentage = 80.0): bool
    {
        $usagePercentage = (memory_get_usage() / self::getMemoryLimit()) * 100;
        return $usagePercentage > $thresholdPercentage;
    }
    
    /**
     * Log memory warning if usage is high
     */
    public static function checkMemoryWarning(string $context = '', float $thresholdPercentage = 80.0): void
    {
        if (self::isMemoryUsageHigh($thresholdPercentage)) {
            Log::warning('High memory usage detected', [
                'context' => $context,
                'memory_stats' => self::getCurrentMemoryUsage()
            ]);
        }
    }
    
    /**
     * Get memory limit in bytes
     */
    private static function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit == -1) {
            return PHP_INT_MAX;
        }
        
        // Convert shorthand notation to bytes
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);
        
        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }
        
        return $value;
    }
}