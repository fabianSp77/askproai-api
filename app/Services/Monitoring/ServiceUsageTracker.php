<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ServiceUsageTracker
{
    private static $instance;
    private $enabled = true;
    private $batchSize = 100;
    private $buffer = [];
    
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Track service usage with comprehensive context
     */
    public function track(string $serviceName, string $method, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }
        
        try {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = $backtrace[2] ?? null;
            
            $data = [
                'service_name' => $serviceName,
                'service_version' => $this->detectServiceVersion($serviceName),
                'method' => $method,
                'parameters' => json_encode($context['parameters'] ?? []),
                'context' => json_encode($context),
                'company_id' => $this->getCurrentCompanyId(),
                'user_id' => auth()->id(),
                'request_id' => request()->header('X-Request-ID', Str::uuid()->toString()),
                'session_id' => session()->getId(),
                'execution_time_ms' => $context['execution_time'] ?? null,
                'success' => $context['success'] ?? true,
                'error_message' => $context['error'] ?? null,
                'caller_class' => $caller['class'] ?? null,
                'caller_method' => $caller['function'] ?? null,
                'caller_line' => $caller['line'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $this->buffer[] = $data;
            
            // Flush buffer if it's full
            if (count($this->buffer) >= $this->batchSize) {
                $this->flush();
            }
            
        } catch (\Exception $e) {
            // Don't let tracking errors break the application
            Log::error('Service usage tracking failed', [
                'error' => $e->getMessage(),
                'service' => $serviceName,
                'method' => $method
            ]);
        }
    }
    
    /**
     * Track service call with automatic timing
     */
    public function trackWithTiming(string $serviceName, string $method, callable $callback, array $context = [])
    {
        $startTime = microtime(true);
        $success = true;
        $error = null;
        $result = null;
        
        try {
            $result = $callback();
        } catch (\Exception $e) {
            $success = false;
            $error = $e->getMessage();
            throw $e;
        } finally {
            $executionTime = (microtime(true) - $startTime) * 1000; // Convert to ms
            
            $this->track($serviceName, $method, array_merge($context, [
                'execution_time' => $executionTime,
                'success' => $success,
                'error' => $error
            ]));
        }
        
        return $result;
    }
    
    /**
     * Detect service version from class name
     */
    private function detectServiceVersion(string $serviceName): ?string
    {
        if (str_contains($serviceName, 'V2')) {
            return 'v2';
        }
        if (str_contains($serviceName, 'V1')) {
            return 'v1';
        }
        if (str_contains($serviceName, 'Enhanced')) {
            return 'enhanced';
        }
        if (str_contains($serviceName, 'Legacy')) {
            return 'legacy';
        }
        return null;
    }
    
    /**
     * Get current company ID from various sources
     */
    private function getCurrentCompanyId(): ?string
    {
        // Try auth user
        if ($user = auth()->user()) {
            return $user->company_id ?? null;
        }
        
        // Try session
        if ($companyId = session('company_id')) {
            return $companyId;
        }
        
        // Try request header
        if ($companyId = request()->header('X-Company-ID')) {
            return $companyId;
        }
        
        return null;
    }
    
    /**
     * Flush buffer to database
     */
    public function flush(): void
    {
        if (empty($this->buffer)) {
            return;
        }
        
        try {
            // Use mcp_metrics table instead of deleted service_usage_logs
            $metricsData = array_map(function($log) {
                return [
                    'service' => $log['service_name'] ?? 'unknown',
                    'operation' => $log['method'] ?? 'unknown',
                    'success' => $log['success'] ?? true,
                    'duration_ms' => $log['execution_time_ms'] ?? 0,
                    'tenant_id' => is_numeric($log['company_id'] ?? null) ? $log['company_id'] : null,
                    'metadata' => json_encode($log),
                    'created_at' => $log['created_at'] ?? now(),
                    'updated_at' => now()
                ];
            }, $this->buffer);
            
            DB::table('mcp_metrics')->insert($metricsData);
            $this->buffer = [];
        } catch (\Exception $e) {
            Log::error('Failed to flush service usage buffer', [
                'error' => $e->getMessage(),
                'buffer_size' => count($this->buffer)
            ]);
        }
    }
    
    /**
     * Get usage statistics for analysis
     */
    public function getUsageStats(string $serviceName = null, int $hours = 24): array
    {
        $query = DB::table('mcp_metrics')
            ->where('created_at', '>=', now()->subHours($hours));
            
        if ($serviceName) {
            $query->where('service', $serviceName);
        }
        
        $results = $query->get();
        $totalCalls = $results->count();
        $successCount = $results->where('success', true)->count();
        
        return [
            'total_calls' => $totalCalls,
            'unique_methods' => $results->pluck('metric_name')->unique()->count(),
            'error_rate' => $totalCalls > 0 ? (($totalCalls - $successCount) / $totalCalls) : 0,
            'avg_execution_time' => $results->avg('duration_ms'),
            'by_service' => $results->groupBy('service')->map(function($group, $service) {
                    return [
                        'service_name' => $service,
                        'calls' => $group->count(),
                        'avg_time' => $group->avg('duration_ms')
                    ];
                })->values(),
            'by_method' => DB::table('mcp_metrics')
                ->where('created_at', '>=', now()->subHours($hours))
                ->when($serviceName, fn($q) => $q->where('service', $serviceName))
                ->groupBy('operation')
                ->selectRaw('operation as method, COUNT(*) as calls')
                ->orderByDesc('calls')
                ->limit(10)
                ->get(),
            'deprecated_usage' => $this->getDeprecatedUsageCount($serviceName, $hours)
        ];
    }
    
    /**
     * Enable/disable tracking
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
    
    /**
     * Get deprecated usage count from mcp_metrics
     */
    private function getDeprecatedUsageCount(string $serviceName = null, int $hours = 24): int
    {
        $query = DB::table('mcp_metrics')
            ->where('created_at', '>=', now()->subHours($hours));
            
        if ($serviceName) {
            $query->where('service', $serviceName);
        }
        
        // Count metrics for deprecated services
        $count = 0;
        $results = $query->get();
        
        foreach ($results as $result) {
            $metadata = json_decode($result->metadata, true);
            $service = $result->service ?? '';
            $version = $metadata['service_version'] ?? '';
            
            if ($version === 'legacy' || 
                str_contains($service, 'Old') || 
                str_contains($service, 'Deprecated')) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Destructor to flush remaining buffer
     */
    public function __destruct()
    {
        $this->flush();
    }
}