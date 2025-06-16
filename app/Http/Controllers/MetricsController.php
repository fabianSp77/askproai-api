<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

class MetricsController extends Controller
{
    private CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Export metrics for Prometheus
     */
    public function index(Request $request): Response
    {
        // Update queue metrics before export
        $this->updateQueueMetrics();
        
        // Update active calls metric
        $this->updateActiveCallsMetric();
        
        // Render metrics in Prometheus format
        $renderer = new RenderTextFormat();
        $metrics = $renderer->render($this->registry->getMetricFamilySamples());
        
        return response($metrics, 200, [
            'Content-Type' => RenderTextFormat::MIME_TYPE,
        ]);
    }

    /**
     * Update queue size metrics
     */
    private function updateQueueMetrics(): void
    {
        try {
            $gauge = $this->registry->getGauge('askproai', 'queue_size');
            
            // Get queue sizes from Redis
            $queues = ['default', 'high', 'low'];
            
            foreach ($queues as $queue) {
                $size = \Redis::llen("queues:{$queue}");
                $gauge->set($size, ['queue' => $queue]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to update queue metrics: ' . $e->getMessage());
        }
    }

    /**
     * Update active calls metric
     */
    private function updateActiveCallsMetric(): void
    {
        try {
            $gauge = $this->registry->getGauge('askproai', 'active_calls');
            
            // Get active calls from database
            $activeCalls = \DB::table('calls')
                ->where('status', 'in_progress')
                ->selectRaw('agent_id, count(*) as count')
                ->groupBy('agent_id')
                ->get();
            
            foreach ($activeCalls as $call) {
                $gauge->set($call->count, ['agent_id' => $call->agent_id ?? 'unknown']);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to update active calls metric: ' . $e->getMessage());
        }
    }
}