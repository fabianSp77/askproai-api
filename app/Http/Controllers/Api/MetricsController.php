<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\MetricsCollector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Prometheus-compatible metrics endpoint
 */
class MetricsController extends Controller
{
    private MetricsCollector $collector;
    
    public function __construct(MetricsCollector $collector)
    {
        $this->collector = $collector;
    }
    
    /**
     * Export metrics in Prometheus format
     */
    public function index(Request $request)
    {
        // Basic auth for security
        $configToken = config('performance-monitoring.metrics_token') ?? config('monitoring.metrics_token') ?? 'default-token';
        if ($request->header('Authorization') !== 'Bearer ' . $configToken) {
            return response('Unauthorized', 401);
        }
        
        $metrics = $this->collector->collect();
        
        return response($this->formatPrometheus($metrics))
            ->header('Content-Type', 'text/plain; version=0.0.4');
    }
    
    /**
     * Health check endpoint
     */
    public function health()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'disk' => $this->checkDiskSpace(),
        ];
        
        $healthy = !in_array(false, $checks, true);
        
        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
    
    /**
     * Format metrics for Prometheus
     */
    private function formatPrometheus(array $metrics): string
    {
        $output = [];
        
        foreach ($metrics as $metric) {
            // Add help text
            if (isset($metric['help'])) {
                $output[] = "# HELP {$metric['name']} {$metric['help']}";
            }
            
            // Add type
            $output[] = "# TYPE {$metric['name']} {$metric['type']}";
            
            // Add metric values
            foreach ($metric['values'] as $value) {
                $labels = '';
                if (!empty($value['labels'])) {
                    $labelPairs = [];
                    foreach ($value['labels'] as $key => $val) {
                        $labelPairs[] = $key . '="' . addslashes($val) . '"';
                    }
                    $labels = '{' . implode(',', $labelPairs) . '}';
                }
                
                $output[] = "{$metric['name']}{$labels} {$value['value']}";
            }
            
            $output[] = ''; // Empty line between metrics
        }
        
        return implode("\n", $output);
    }
    
    private function checkDatabase(): bool
    {
        try {
            DB::select('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    private function checkRedis(): bool
    {
        try {
            Cache::store('redis')->get('health_check');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    private function checkQueue(): bool
    {
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            return $failedJobs < 100; // Threshold
        } catch (\Exception $e) {
            return false;
        }
    }
    
    private function checkDiskSpace(): bool
    {
        $free = disk_free_space('/');
        $total = disk_total_space('/');
        
        return ($free / $total) > 0.1; // At least 10% free
    }
}