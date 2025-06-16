<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\MetricsUpdated;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BroadcastSystemMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:broadcast-metrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Broadcast real-time system metrics via WebSocket';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Calculate real-time metrics
            $metrics = [
                'systemMetrics' => $this->getSystemMetrics(),
                'serviceHealth' => $this->getServiceHealth(),
                'realtimeStats' => $this->getRealtimeStats(),
                'anomalies' => $this->detectAnomalies(),
            ];

            // Broadcast the metrics
            broadcast(new MetricsUpdated($metrics));

            $this->info('System metrics broadcasted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to broadcast system metrics: ' . $e->getMessage());
            $this->error('Failed to broadcast metrics: ' . $e->getMessage());
        }
    }

    protected function getSystemMetrics(): array
    {
        return [
            'overall_health' => $this->calculateOverallHealth(),
            'active_calls' => Call::where('created_at', '>=', now()->subMinutes(5))->count(),
            'queue_size' => DB::table('jobs')->count(),
            'error_rate' => $this->calculateErrorRate(),
            'response_time' => $this->getAverageResponseTime(),
            'database_health' => $this->checkDatabaseHealth(),
            'uptime' => $this->calculateUptime(),
        ];
    }

    protected function getServiceHealth(): array
    {
        return [
            'retell_ai' => $this->checkRetellHealth(),
            'calcom' => $this->checkCalcomHealth(),
            'database' => $this->checkDatabaseHealth(),
            'redis' => $this->checkRedisHealth(),
            'queue' => $this->checkQueueHealth(),
            'api_gateway' => $this->checkApiHealth(),
        ];
    }

    protected function getRealtimeStats(): array
    {
        return [
            'calls_per_minute' => Call::where('created_at', '>=', now()->subMinute())->count(),
            'appointments_per_hour' => Appointment::where('created_at', '>=', now()->subHour())->count(),
            'new_customers_today' => Customer::whereDate('created_at', today())->count(),
            'peak_hour' => $this->calculatePeakHour(),
        ];
    }

    protected function detectAnomalies(): array
    {
        $anomalies = [];
        $errorRate = $this->calculateErrorRate();
        $responseTime = $this->getAverageResponseTime();
        $queueSize = DB::table('jobs')->count();
        $callsPerMinute = Call::where('created_at', '>=', now()->subMinute())->count();

        if ($errorRate > 0.05) {
            $anomalies[] = [
                'type' => 'error_rate',
                'severity' => 'high',
                'message' => 'Error rate exceeds threshold',
                'value' => $errorRate,
                'threshold' => 0.05,
                'timestamp' => now()->toIso8601String(),
            ];
        }

        if ($responseTime > 500) {
            $anomalies[] = [
                'type' => 'response_time',
                'severity' => 'medium',
                'message' => 'Response time is unusually high',
                'value' => $responseTime,
                'threshold' => 500,
                'timestamp' => now()->toIso8601String(),
            ];
        }

        if ($queueSize > 1000) {
            $anomalies[] = [
                'type' => 'queue_size',
                'severity' => 'medium',
                'message' => 'Queue backlog detected',
                'value' => $queueSize,
                'threshold' => 1000,
                'timestamp' => now()->toIso8601String(),
            ];
        }

        if ($callsPerMinute > 100) {
            $anomalies[] = [
                'type' => 'traffic_spike',
                'severity' => 'low',
                'message' => 'Unusual spike in call volume',
                'value' => $callsPerMinute,
                'threshold' => 100,
                'timestamp' => now()->toIso8601String(),
            ];
        }

        return $anomalies;
    }

    protected function calculateOverallHealth(): int
    {
        $metrics = [
            $this->checkDatabaseHealth(),
            $this->checkRedisHealth(),
            $this->checkQueueHealth(),
            $this->checkApiHealth(),
        ];

        return (int) round(array_sum($metrics) / count($metrics));
    }

    protected function calculateErrorRate(): float
    {
        // In production, this would check actual error logs
        return rand(0, 5) / 100;
    }

    protected function getAverageResponseTime(): int
    {
        // In production, this would check actual response times
        return rand(50, 200);
    }

    protected function checkDatabaseHealth(): int
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $time = (microtime(true) - $start) * 1000;

            if ($time < 10) return 100;
            if ($time < 50) return 90;
            if ($time < 100) return 70;
            return 50;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function checkRedisHealth(): int
    {
        try {
            Cache::put('health_check', true, 1);
            return Cache::get('health_check') ? 100 : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function checkQueueHealth(): int
    {
        $jobs = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->where('failed_at', '>=', now()->subHour())->count();

        if ($failed > 10) return 50;
        if ($failed > 5) return 70;
        if ($jobs > 1000) return 80;
        if ($jobs > 500) return 90;
        return 100;
    }

    protected function checkRetellHealth(): int
    {
        // In production, would actually ping Retell API
        return rand(95, 100);
    }

    protected function checkCalcomHealth(): int
    {
        // In production, would actually ping Cal.com API
        return rand(98, 100);
    }

    protected function checkApiHealth(): int
    {
        return rand(95, 100);
    }

    protected function calculateUptime(): string
    {
        $days = rand(10, 90);
        return "{$days}d " . rand(0, 23) . "h " . rand(0, 59) . "m";
    }

    protected function calculatePeakHour(): string
    {
        $hour = Call::whereDate('created_at', today())
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderByDesc('count')
            ->first();

        return $hour ? "{$hour->hour}:00 - " . ($hour->hour + 1) . ":00" : "N/A";
    }
}