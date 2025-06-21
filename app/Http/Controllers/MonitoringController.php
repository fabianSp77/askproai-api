<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Monitoring\HealthCheckService;
use App\Services\Monitoring\PerformanceMonitor;
use App\Services\Monitoring\SecurityMonitor;
use App\Services\Monitoring\AlertingService;
use Illuminate\Support\Facades\Cache;

class MonitoringController extends Controller
{
    protected HealthCheckService $healthCheck;
    protected PerformanceMonitor $performance;
    protected SecurityMonitor $security;
    protected AlertingService $alerting;

    public function __construct(
        HealthCheckService $healthCheck,
        PerformanceMonitor $performance,
        SecurityMonitor $security,
        AlertingService $alerting
    ) {
        $this->healthCheck = $healthCheck;
        $this->performance = $performance;
        $this->security = $security;
        $this->alerting = $alerting;
    }

    /**
     * Health check endpoint
     */
    public function health(Request $request): JsonResponse
    {
        // Verify secret if configured
        $secret = config('monitoring.health_checks.secret');
        if ($secret && $request->header('X-Health-Check-Secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $results = $this->healthCheck->check();

        $statusCode = $results['status'] === 'healthy' ? 200 : 503;

        return response()->json($results, $statusCode);
    }

    /**
     * Metrics endpoint for Prometheus/Grafana
     */
    public function metrics(Request $request): string
    {
        // Verify secret if configured
        $secret = config('monitoring.metrics.secret');
        if ($secret && $request->header('X-Metrics-Secret') !== $secret) {
            abort(401);
        }

        $metrics = $this->collectMetrics();

        return $this->formatPrometheusMetrics($metrics);
    }

    /**
     * Monitoring dashboard data
     */
    public function dashboard(): JsonResponse
    {
        $this->authorize('view-monitoring');

        $data = [
            'health' => $this->healthCheck->check(),
            'alerts' => $this->alerting->getActiveAlerts(),
            'security' => $this->security->getMetrics(),
            'performance' => $this->getPerformanceMetrics(),
            'business' => $this->getBusinessMetrics(),
        ];

        return response()->json($data);
    }

    /**
     * Collect all metrics
     */
    private function collectMetrics(): array
    {
        $metrics = [];

        // System metrics
        $metrics['askproai_up'] = 1;
        $metrics['askproai_php_memory_usage_bytes'] = memory_get_usage(true);
        $metrics['askproai_php_memory_peak_bytes'] = memory_get_peak_usage(true);

        // Request metrics
        $requestMetrics = Cache::get('metrics:request:' . date('YmdH'), []);
        if (!empty($requestMetrics)) {
            $metrics['askproai_http_requests_total'] = $requestMetrics['count'] ?? 0;
            $metrics['askproai_http_request_duration_ms'] = $requestMetrics['avg_duration'] ?? 0;
            $metrics['askproai_http_requests_errors_total'] = $requestMetrics['errors'] ?? 0;
        }

        // Queue metrics
        $queues = ['default', 'webhooks', 'stripe', 'emails'];
        foreach ($queues as $queue) {
            $size = \Illuminate\Support\Facades\Redis::connection()->llen("queue:$queue");
            $metrics["askproai_queue_size{queue=\"$queue\"}"] = $size;
        }

        // Business metrics
        if (config('monitoring.metrics.business.subscriptions_created')) {
            $metrics['askproai_subscriptions_created_total'] = Cache::get('business_metrics:subscriptions_created', 0);
        }
        
        if (config('monitoring.metrics.business.revenue_processed')) {
            $metrics['askproai_revenue_processed_cents'] = Cache::get('business_metrics:revenue_processed', 0);
        }

        // Security metrics
        $securityMetrics = $this->security->getMetrics();
        $metrics['askproai_failed_logins_total'] = $securityMetrics['failed_logins_24h'];
        $metrics['askproai_blocked_ips_total'] = $securityMetrics['blocked_ips'];
        $metrics['askproai_rate_limit_violations_total'] = $securityMetrics['rate_limit_violations_1h'];

        // API metrics
        $services = ['stripe', 'calcom', 'retell'];
        foreach ($services as $service) {
            $apiMetrics = $this->performance->getApiMetrics($service);
            if ($apiMetrics['total_calls'] > 0) {
                $metrics["askproai_external_api_calls_total{service=\"$service\"}"] = $apiMetrics['total_calls'];
                $metrics["askproai_external_api_errors_total{service=\"$service\"}"] = $apiMetrics['failed_calls'];
                $metrics["askproai_external_api_duration_ms{service=\"$service\"}"] = $apiMetrics['avg_duration'] ?? 0;
            }
        }

        return $metrics;
    }

    /**
     * Format metrics for Prometheus
     */
    private function formatPrometheusMetrics(array $metrics): string
    {
        $output = '';
        
        foreach ($metrics as $name => $value) {
            // Add HELP and TYPE comments for main metrics
            if (!str_contains($name, '{')) {
                $help = $this->getMetricHelp($name);
                $type = $this->getMetricType($name);
                
                if ($help) {
                    $output .= "# HELP $name $help\n";
                }
                if ($type) {
                    $output .= "# TYPE $name $type\n";
                }
            }
            
            $output .= "$name $value\n";
        }

        return $output;
    }

    /**
     * Get metric help text
     */
    private function getMetricHelp(string $metric): ?string
    {
        $helps = [
            'askproai_up' => 'Whether the AskProAI service is up',
            'askproai_http_requests_total' => 'Total number of HTTP requests',
            'askproai_http_request_duration_ms' => 'Average HTTP request duration in milliseconds',
            'askproai_queue_size' => 'Current size of the job queue',
            'askproai_subscriptions_created_total' => 'Total number of subscriptions created',
            'askproai_revenue_processed_cents' => 'Total revenue processed in cents',
            'askproai_failed_logins_total' => 'Total number of failed login attempts',
            'askproai_external_api_calls_total' => 'Total number of external API calls',
        ];

        return $helps[$metric] ?? null;
    }

    /**
     * Get metric type
     */
    private function getMetricType(string $metric): string
    {
        if (str_contains($metric, '_total')) {
            return 'counter';
        }
        if (str_contains($metric, '_bytes') || str_contains($metric, '_size')) {
            return 'gauge';
        }
        if (str_contains($metric, '_duration_')) {
            return 'histogram';
        }
        
        return 'gauge';
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        return [
            'request_metrics' => $this->performance->getMetrics('request'),
            'stripe_webhook_metrics' => $this->performance->getMetrics('stripe_webhook'),
            'customer_portal_metrics' => $this->performance->getMetrics('customer_portal'),
            'api_metrics' => [
                'stripe' => $this->performance->getApiMetrics('stripe'),
                'calcom' => $this->performance->getApiMetrics('calcom'),
                'retell' => $this->performance->getApiMetrics('retell'),
            ],
        ];
    }

    /**
     * Get business metrics
     */
    private function getBusinessMetrics(): array
    {
        return [
            'subscriptions_created_today' => Cache::get('business_metrics:subscriptions_created:' . date('Ymd'), 0),
            'revenue_processed_today' => Cache::get('business_metrics:revenue_processed:' . date('Ymd'), 0),
            'portal_registrations_today' => Cache::get('business_metrics:portal_registrations:' . date('Ymd'), 0),
            'portal_logins_today' => Cache::get('business_metrics:portal_logins:' . date('Ymd'), 0),
        ];
    }
}