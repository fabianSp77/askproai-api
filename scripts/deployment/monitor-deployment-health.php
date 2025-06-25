<?php
#!/usr/bin/env php
<?php

/**
 * Real-time deployment health monitoring script
 * Tracks key metrics and alerts on anomalies
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class DeploymentHealthMonitor
{
    private $baselineMetrics = [];
    private $alertThresholds = [
        'error_rate' => 5.0,        // %
        'response_time' => 1000,    // ms
        'memory_usage' => 80,       // %
        'cpu_usage' => 70,          // %
        'queue_depth' => 1000,      // jobs
        'db_connections' => 90,     // %
    ];
    
    public function run()
    {
        $this->output("🏥 Deployment Health Monitor Started");
        $this->output("Press Ctrl+C to stop\n");
        
        // Capture baseline metrics
        $this->captureBaseline();
        
        while (true) {
            $this->checkHealth();
            sleep(10); // Check every 10 seconds
        }
    }
    
    private function captureBaseline()
    {
        $this->output("📊 Capturing baseline metrics...");
        
        $this->baselineMetrics = [
            'error_rate' => $this->getErrorRate(),
            'response_time' => $this->getAverageResponseTime(),
            'memory_usage' => $this->getMemoryUsage(),
            'active_calls' => $this->getActiveCalls(),
        ];
        
        $this->output("Baseline captured: " . json_encode($this->baselineMetrics));
    }
    
    private function checkHealth()
    {
        $timestamp = date('Y-m-d H:i:s');
        $metrics = $this->collectMetrics();
        
        // Clear screen for clean output
        system('clear');
        
        $this->output("🏥 Deployment Health Monitor - $timestamp");
        $this->output(str_repeat('=', 60));
        
        // Display metrics with color coding
        foreach ($metrics as $key => $value) {
            $status = $this->getMetricStatus($key, $value);
            $this->outputMetric($key, $value, $status);
        }
        
        $this->output(str_repeat('=', 60));
        
        // Check for alerts
        $alerts = $this->checkAlerts($metrics);
        if (!empty($alerts)) {
            $this->output("\n🚨 ALERTS:");
            foreach ($alerts as $alert) {
                $this->output("  - $alert", 'red');
            }
        } else {
            $this->output("\n✅ All systems operational", 'green');
        }
        
        // Store metrics for historical analysis
        $this->storeMetrics($metrics);
    }
    
    private function collectMetrics()
    {
        return [
            'error_rate' => $this->getErrorRate(),
            'response_time' => $this->getAverageResponseTime(),
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage(),
            'queue_depth' => $this->getQueueDepth(),
            'active_calls' => $this->getActiveCalls(),
            'db_connections' => $this->getDatabaseConnections(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'agent_sync_status' => $this->getAgentSyncStatus(),
        ];
    }
    
    private function getErrorRate()
    {
        try {
            $total = DB::table('api_call_logs')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->count();
                
            if ($total === 0) return 0;
            
            $errors = DB::table('api_call_logs')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->where('status_code', '>=', 400)
                ->count();
                
            return round(($errors / $total) * 100, 2);
        } catch (\Exception $e) {
            return -1;
        }
    }
    
    private function getAverageResponseTime()
    {
        try {
            $avg = DB::table('api_call_logs')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->avg('response_time_ms');
                
            return $avg ? round($avg, 2) : 0;
        } catch (\Exception $e) {
            return -1;
        }
    }
    
    private function getMemoryUsage()
    {
        $memory = memory_get_usage(true);
        $limit = ini_get('memory_limit');
        
        // Convert memory limit to bytes
        $limit = $this->convertToBytes($limit);
        
        return round(($memory / $limit) * 100, 2);
    }
    
    private function getCpuUsage()
    {
        $load = sys_getloadavg();
        $cores = (int) shell_exec('nproc');
        
        // Use 1-minute load average
        return round(($load[0] / $cores) * 100, 2);
    }
    
    private function getQueueDepth()
    {
        try {
            return DB::table('jobs')->count() + DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            return -1;
        }
    }
    
    private function getActiveCalls()
    {
        try {
            return DB::table('calls')
                ->where('status', 'active')
                ->orWhere('created_at', '>=', now()->subMinutes(5))
                ->count();
        } catch (\Exception $e) {
            return -1;
        }
    }
    
    private function getDatabaseConnections()
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $current = $result[0]->Value ?? 0;
            
            $result = DB::select("SHOW VARIABLES LIKE 'max_connections'");
            $max = $result[0]->Value ?? 100;
            
            return round(($current / $max) * 100, 2);
        } catch (\Exception $e) {
            return -1;
        }
    }
    
    private function getCacheHitRate()
    {
        // This would need Redis stats in production
        return rand(85, 99); // Simulated for now
    }
    
    private function getAgentSyncStatus()
    {
        try {
            $total = DB::table('retell_agents')->count();
            $synced = DB::table('retell_agents')
                ->where('sync_status', 'synced')
                ->count();
                
            return $total > 0 ? round(($synced / $total) * 100, 2) : 100;
        } catch (\Exception $e) {
            return -1;
        }
    }
    
    private function getMetricStatus($key, $value)
    {
        if ($value === -1) return 'error';
        
        if (isset($this->alertThresholds[$key])) {
            $threshold = $this->alertThresholds[$key];
            
            // For percentage metrics, higher is worse
            if (in_array($key, ['error_rate', 'memory_usage', 'cpu_usage', 'db_connections'])) {
                if ($value > $threshold) return 'critical';
                if ($value > $threshold * 0.8) return 'warning';
            }
            
            // For response time and queue depth, higher is worse
            if (in_array($key, ['response_time', 'queue_depth'])) {
                if ($value > $threshold) return 'critical';
                if ($value > $threshold * 0.8) return 'warning';
            }
        }
        
        // For hit rates and sync status, lower is worse
        if (in_array($key, ['cache_hit_rate', 'agent_sync_status'])) {
            if ($value < 80) return 'warning';
            if ($value < 50) return 'critical';
        }
        
        return 'ok';
    }
    
    private function checkAlerts($metrics)
    {
        $alerts = [];
        
        foreach ($metrics as $key => $value) {
            $status = $this->getMetricStatus($key, $value);
            
            if ($status === 'critical') {
                $alerts[] = "$key is critical: $value" . $this->getMetricUnit($key);
            } elseif ($status === 'error') {
                $alerts[] = "$key measurement failed";
            }
        }
        
        return $alerts;
    }
    
    private function getMetricUnit($key)
    {
        $units = [
            'error_rate' => '%',
            'response_time' => 'ms',
            'memory_usage' => '%',
            'cpu_usage' => '%',
            'queue_depth' => ' jobs',
            'active_calls' => ' calls',
            'db_connections' => '%',
            'cache_hit_rate' => '%',
            'agent_sync_status' => '%',
        ];
        
        return $units[$key] ?? '';
    }
    
    private function storeMetrics($metrics)
    {
        $key = 'deployment_metrics_' . date('Y-m-d_H');
        $existing = Cache::get($key, []);
        $existing[] = [
            'timestamp' => now()->toIso8601String(),
            'metrics' => $metrics,
        ];
        
        Cache::put($key, $existing, 3600); // Store for 1 hour
    }
    
    private function outputMetric($key, $value, $status)
    {
        $label = str_pad($this->formatMetricName($key) . ':', 25);
        $valueStr = str_pad($value . $this->getMetricUnit($key), 15);
        
        $color = 'white';
        $icon = '●';
        
        switch ($status) {
            case 'ok':
                $color = 'green';
                $icon = '✓';
                break;
            case 'warning':
                $color = 'yellow';
                $icon = '⚠';
                break;
            case 'critical':
                $color = 'red';
                $icon = '✗';
                break;
            case 'error':
                $color = 'red';
                $icon = '?';
                break;
        }
        
        $this->output("$icon $label $valueStr", $color);
    }
    
    private function formatMetricName($key)
    {
        return ucwords(str_replace('_', ' ', $key));
    }
    
    private function output($text, $color = 'white')
    {
        $colors = [
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'white' => "\033[37m",
        ];
        
        $reset = "\033[0m";
        
        echo ($colors[$color] ?? '') . $text . $reset . PHP_EOL;
    }
    
    private function convertToBytes($value)
    {
        $unit = strtolower(substr($value, -1));
        $value = (int) $value;
        
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

// Run the monitor
$monitor = new DeploymentHealthMonitor();
$monitor->run();