<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait MemoryAwareJob
{
    /**
     * Memory usage before job execution
     */
    protected int $initialMemoryUsage = 0;
    
    /**
     * Maximum memory limit in bytes (256MB default)
     */
    protected int $memoryLimitBytes = 268435456; // 256 * 1024 * 1024
    
    /**
     * Memory usage threshold for warnings (80% of limit)
     */
    protected float $memoryWarningThreshold = 0.8;
    
    /**
     * Chunk size for processing large datasets
     */
    protected int $defaultChunkSize = 100;
    
    /**
     * Memory monitoring enabled flag
     */
    protected bool $memoryMonitoringEnabled = true;
    
    /**
     * Initialize memory monitoring
     */
    protected function initializeMemoryMonitoring(): void
    {
        if (!$this->memoryMonitoringEnabled) {
            return;
        }
        
        $this->initialMemoryUsage = memory_get_usage(true);
        
        Log::info('Job memory monitoring initialized', [
            'job' => static::class,
            'initial_memory_mb' => $this->bytesToMb($this->initialMemoryUsage),
            'memory_limit_mb' => $this->bytesToMb($this->memoryLimitBytes),
            'php_memory_limit' => ini_get('memory_limit')
        ]);
    }
    
    /**
     * Check current memory usage and take action if needed
     */
    protected function checkMemoryUsage(string $operation = 'operation'): void
    {
        if (!$this->memoryMonitoringEnabled) {
            return;
        }
        
        $currentUsage = memory_get_usage(true);
        $peakUsage = memory_get_peak_usage(true);
        $usageIncrease = $currentUsage - $this->initialMemoryUsage;
        
        $currentMb = $this->bytesToMb($currentUsage);
        $peakMb = $this->bytesToMb($peakUsage);
        $limitMb = $this->bytesToMb($this->memoryLimitBytes);
        
        // Check if we're approaching memory limit
        if ($currentUsage >= $this->memoryLimitBytes * $this->memoryWarningThreshold) {
            Log::warning('Job approaching memory limit', [
                'job' => static::class,
                'operation' => $operation,
                'current_memory_mb' => $currentMb,
                'peak_memory_mb' => $peakMb,
                'memory_limit_mb' => $limitMb,
                'usage_percentage' => ($currentUsage / $this->memoryLimitBytes) * 100
            ]);
            
            // Force garbage collection
            $this->forceGarbageCollection();
        }
        
        // Fail if memory limit exceeded
        if ($currentUsage >= $this->memoryLimitBytes) {
            $this->handleMemoryLimitExceeded($operation, $currentMb, $limitMb);
        }
        
        // Log memory usage every 50MB increase
        if ($usageIncrease > 0 && $usageIncrease % (50 * 1024 * 1024) === 0) {
            Log::info('Job memory usage update', [
                'job' => static::class,
                'operation' => $operation,
                'current_memory_mb' => $currentMb,
                'peak_memory_mb' => $peakMb,
                'increase_mb' => $this->bytesToMb($usageIncrease)
            ]);
        }
    }
    
    /**
     * Force garbage collection and log results
     */
    protected function forceGarbageCollection(): int
    {
        $memoryBefore = memory_get_usage(true);
        
        // Explicitly unset large variables if they exist
        if (method_exists($this, 'clearLargeVariables')) {
            $this->clearLargeVariables();
        }
        
        // Force garbage collection
        $cycles = gc_collect_cycles();
        
        $memoryAfter = memory_get_usage(true);
        $memoryFreed = $memoryBefore - $memoryAfter;
        
        Log::info('Garbage collection completed', [
            'job' => static::class,
            'cycles_collected' => $cycles,
            'memory_freed_mb' => $this->bytesToMb($memoryFreed),
            'memory_before_mb' => $this->bytesToMb($memoryBefore),
            'memory_after_mb' => $this->bytesToMb($memoryAfter)
        ]);
        
        return $cycles;
    }
    
    /**
     * Handle memory limit exceeded situation
     */
    protected function handleMemoryLimitExceeded(string $operation, float $currentMb, float $limitMb): void
    {
        Log::critical('Job memory limit exceeded', [
            'job' => static::class,
            'operation' => $operation,
            'current_memory_mb' => $currentMb,
            'memory_limit_mb' => $limitMb
        ]);
        
        // Try emergency garbage collection
        $this->forceGarbageCollection();
        
        // Check if we're still over the limit
        if (memory_get_usage(true) >= $this->memoryLimitBytes) {
            throw new \RuntimeException(
                "Job memory limit exceeded: {$currentMb}MB > {$limitMb}MB during {$operation}"
            );
        }
    }
    
    /**
     * Process data in memory-safe chunks
     */
    protected function processInChunks($data, callable $processor, int $chunkSize = null): array
    {
        $chunkSize = $chunkSize ?? $this->defaultChunkSize;
        $results = [];
        $totalItems = is_countable($data) ? count($data) : 0;
        
        Log::info('Processing data in chunks', [
            'job' => static::class,
            'total_items' => $totalItems,
            'chunk_size' => $chunkSize
        ]);
        
        // Handle different data types
        if (is_array($data)) {
            $chunks = array_chunk($data, $chunkSize);
        } elseif ($data instanceof \Illuminate\Support\Collection) {
            $chunks = $data->chunk($chunkSize);
        } elseif ($data instanceof \Illuminate\Database\Eloquent\Builder) {
            // For query builders, use chunking method
            $processedCount = 0;
            $data->chunk($chunkSize, function ($chunk) use ($processor, &$results, &$processedCount) {
                $this->checkMemoryUsage("chunk_processing_items_{$processedCount}");
                
                $chunkResults = $processor($chunk);
                if ($chunkResults) {
                    $results = array_merge($results, is_array($chunkResults) ? $chunkResults : [$chunkResults]);
                }
                
                $processedCount += $chunk->count();
                $this->forceGarbageCollection();
                
                Log::debug('Chunk processed', [
                    'job' => static::class,
                    'processed_items' => $processedCount,
                    'memory_mb' => $this->bytesToMb(memory_get_usage(true))
                ]);
            });
            
            return $results;
        } else {
            throw new \InvalidArgumentException('Unsupported data type for chunking');
        }
        
        // Process chunks
        $processedChunks = 0;
        foreach ($chunks as $chunk) {
            $this->checkMemoryUsage("chunk_processing_{$processedChunks}");
            
            $chunkResults = $processor($chunk);
            if ($chunkResults) {
                $results = array_merge($results, is_array($chunkResults) ? $chunkResults : [$chunkResults]);
            }
            
            $processedChunks++;
            
            // Force garbage collection after each chunk
            $this->forceGarbageCollection();
            
            Log::debug('Chunk processed', [
                'job' => static::class,
                'chunk_number' => $processedChunks,
                'total_chunks' => count($chunks),
                'memory_mb' => $this->bytesToMb(memory_get_usage(true))
            ]);
        }
        
        return $results;
    }
    
    /**
     * Finalize memory monitoring and log final statistics
     */
    protected function finalizeMemoryMonitoring(): void
    {
        if (!$this->memoryMonitoringEnabled) {
            return;
        }
        
        $finalUsage = memory_get_usage(true);
        $peakUsage = memory_get_peak_usage(true);
        $totalIncrease = $finalUsage - $this->initialMemoryUsage;
        
        Log::info('Job memory monitoring completed', [
            'job' => static::class,
            'initial_memory_mb' => $this->bytesToMb($this->initialMemoryUsage),
            'final_memory_mb' => $this->bytesToMb($finalUsage),
            'peak_memory_mb' => $this->bytesToMb($peakUsage),
            'total_increase_mb' => $this->bytesToMb($totalIncrease),
            'memory_limit_mb' => $this->bytesToMb($this->memoryLimitBytes)
        ]);
        
        // Final garbage collection
        $this->forceGarbageCollection();
    }
    
    /**
     * Convert bytes to megabytes for logging
     */
    protected function bytesToMb(int $bytes): float
    {
        return round($bytes / (1024 * 1024), 2);
    }
    
    /**
     * Set memory limit for this job
     */
    protected function setMemoryLimit(int $limitMb): void
    {
        $this->memoryLimitBytes = $limitMb * 1024 * 1024;
        
        Log::info('Job memory limit set', [
            'job' => static::class,
            'memory_limit_mb' => $limitMb
        ]);
    }
    
    /**
     * Set chunk size for processing
     */
    protected function setChunkSize(int $size): void
    {
        $this->defaultChunkSize = $size;
        
        Log::info('Job chunk size set', [
            'job' => static::class,
            'chunk_size' => $size
        ]);
    }
    
    /**
     * Disable memory monitoring for performance-critical jobs
     */
    protected function disableMemoryMonitoring(): void
    {
        $this->memoryMonitoringEnabled = false;
    }
    
    /**
     * Check if job should be split due to memory constraints
     */
    protected function shouldSplitJob(int $itemCount, int $memoryPerItem = 1024): bool
    {
        $estimatedMemory = $itemCount * $memoryPerItem;
        $availableMemory = $this->memoryLimitBytes - memory_get_usage(true);
        
        return $estimatedMemory > $availableMemory * 0.8; // 80% of available memory
    }
    
    /**
     * Create child jobs to handle large datasets
     */
    protected function createChildJobs($data, string $jobClass, int $maxItemsPerJob = null): array
    {
        $maxItemsPerJob = $maxItemsPerJob ?? $this->defaultChunkSize * 5;
        $childJobs = [];
        
        if (is_array($data)) {
            $chunks = array_chunk($data, $maxItemsPerJob);
        } elseif ($data instanceof \Illuminate\Support\Collection) {
            $chunks = $data->chunk($maxItemsPerJob);
        } else {
            throw new \InvalidArgumentException('Unsupported data type for job splitting');
        }
        
        foreach ($chunks as $chunk) {
            $childJob = new $jobClass($chunk);
            $childJobs[] = $childJob;
        }
        
        Log::info('Child jobs created', [
            'parent_job' => static::class,
            'child_job_class' => $jobClass,
            'total_child_jobs' => count($childJobs),
            'items_per_job' => $maxItemsPerJob
        ]);
        
        return $childJobs;
    }
}