<?php

namespace App\Services\Monitoring;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;
use Illuminate\Support\Facades\Log;

class MetricsCollector
{
    private CollectorRegistry $registry;
    private array $counters = [];
    private array $histograms = [];
    private array $gauges = [];
    
    public function __construct()
    {
        // Initialize Prometheus with Redis backend
        Redis::setDefaultOptions([
            'host' => config('database.redis.default.host'),
            'port' => config('database.redis.default.port'),
            'password' => config('database.redis.default.password'),
            'database' => 2, // Use separate Redis DB for metrics
        ]);
        
        $this->registry = new CollectorRegistry(new Redis());
        $this->initializeMetrics();
    }
    
    /**
     * Initialize all metrics
     */
    private function initializeMetrics(): void
    {
        // HTTP Request metrics
        $this->counters['http_requests'] = $this->registry->getOrRegisterCounter(
            'askproai',
            'http_requests_total',
            'Total number of HTTP requests',
            ['method', 'endpoint', 'status']
        );
        
        $this->histograms['http_duration'] = $this->registry->getOrRegisterHistogram(
            'askproai',
            'http_request_duration_seconds',
            'HTTP request duration',
            ['method', 'endpoint']
        );
        
        // Webhook metrics
        $this->counters['webhooks'] = $this->registry->getOrRegisterCounter(
            'askproai',
            'webhooks_total',
            'Total number of webhooks received',
            ['provider', 'event_type', 'status']
        );
        
        $this->histograms['webhook_duration'] = $this->registry->getOrRegisterHistogram(
            'askproai',
            'webhook_processing_duration_seconds',
            'Webhook processing duration',
            ['provider', 'event_type']
        );
        
        // Business metrics
        $this->counters['bookings'] = $this->registry->getOrRegisterCounter(
            'askproai',
            'bookings_total',
            'Total number of bookings',
            ['status', 'source']
        );
        
        $this->counters['calls'] = $this->registry->getOrRegisterCounter(
            'askproai',
            'calls_total',
            'Total number of calls',
            ['status', 'company_id']
        );
        
        // System metrics
        $this->gauges['queue_size'] = $this->registry->getOrRegisterGauge(
            'askproai',
            'queue_size',
            'Current queue size',
            ['queue']
        );
        
        $this->gauges['active_tenants'] = $this->registry->getOrRegisterGauge(
            'askproai',
            'active_tenants',
            'Number of active tenants'
        );
        
        // Database metrics
        $this->gauges['db_connections'] = $this->registry->getOrRegisterGauge(
            'askproai',
            'database_connections',
            'Current database connections',
            ['pool']
        );
        
        // Error metrics
        $this->counters['errors'] = $this->registry->getOrRegisterCounter(
            'askproai',
            'errors_total',
            'Total number of errors',
            ['type', 'severity']
        );
    }
    
    /**
     * Record HTTP request
     */
    public function recordHttpRequest(string $method, string $endpoint, int $status, float $duration): void
    {
        try {
            $this->counters['http_requests']->incBy(1, [$method, $endpoint, (string)$status]);
            $this->histograms['http_duration']->observe($duration, [$method, $endpoint]);
        } catch (\Exception $e) {
            Log::error('Failed to record HTTP metrics', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Record webhook
     */
    public function recordWebhook(string $provider, string $eventType, string $status, float $duration = null): void
    {
        try {
            $this->counters['webhooks']->incBy(1, [$provider, $eventType, $status]);
            
            if ($duration !== null) {
                $this->histograms['webhook_duration']->observe($duration, [$provider, $eventType]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to record webhook metrics', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Record booking
     */
    public function recordBooking(string $status, string $source = 'phone'): void
    {
        try {
            $this->counters['bookings']->incBy(1, [$status, $source]);
        } catch (\Exception $e) {
            Log::error('Failed to record booking metrics', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Record call
     */
    public function recordCall(string $status, int $companyId): void
    {
        try {
            $this->counters['calls']->incBy(1, [$status, (string)$companyId]);
        } catch (\Exception $e) {
            Log::error('Failed to record call metrics', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Update queue size
     */
    public function updateQueueSize(string $queue, int $size): void
    {
        try {
            $this->gauges['queue_size']->set($size, [$queue]);
        } catch (\Exception $e) {
            Log::error('Failed to update queue size metric', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Update active tenants
     */
    public function updateActiveTenants(int $count): void
    {
        try {
            $this->gauges['active_tenants']->set($count);
        } catch (\Exception $e) {
            Log::error('Failed to update active tenants metric', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Update database connections
     */
    public function updateDatabaseConnections(string $pool, int $active, int $idle): void
    {
        try {
            $this->gauges['db_connections']->set($active + $idle, [$pool]);
        } catch (\Exception $e) {
            Log::error('Failed to update database connection metrics', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Record error
     */
    public function recordError(string $type, string $severity = 'error'): void
    {
        try {
            $this->counters['errors']->incBy(1, [$type, $severity]);
        } catch (\Exception $e) {
            Log::error('Failed to record error metric', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get metrics for export
     */
    public function getMetrics(): array
    {
        return $this->registry->getMetricFamilySamples();
    }
    
    /**
     * Render metrics in Prometheus format
     */
    public function renderMetrics(): string
    {
        $renderer = new \Prometheus\RenderTextFormat();
        return $renderer->render($this->getMetrics());
    }
}