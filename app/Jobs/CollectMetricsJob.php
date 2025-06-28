<?php

namespace App\Jobs;

use App\Services\Monitoring\MetricsCollector;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CollectMetricsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 60;

    /**
     * Execute the job.
     */
    public function handle(MetricsCollector $collector): void
    {
        try {
            // Collect webhook metrics
            $this->collectWebhookMetrics($collector);

            // Collect appointment metrics
            $this->collectAppointmentMetrics($collector);

            // Collect call metrics
            $this->collectCallMetrics($collector);

            // Collect database performance metrics
            $this->collectDatabaseMetrics($collector);

            Log::info('Metrics collection completed successfully');
        } catch (\Exception $e) {
            Log::error('Failed to collect metrics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Collect webhook processing metrics.
     */
    private function collectWebhookMetrics(MetricsCollector $collector): void
    {
        // Get webhook statistics for the last hour
        $webhookStats = DB::table('webhook_events')
            ->selectRaw('provider, status, COUNT(*) as count, AVG(processing_time_ms) as avg_time')
            ->where('created_at', '>=', now()->subHour())
            ->groupBy(['provider', 'status'])
            ->get();

        foreach ($webhookStats as $stat) {
            // Record webhook count
            $collector->recordWebhook(
                $stat->provider,
                'processed',
                $stat->status,
                null
            );

            // Record average processing time
            if ($stat->avg_time > 0) {
                $collector->recordWebhook(
                    $stat->provider,
                    'processed',
                    $stat->status,
                    $stat->avg_time / 1000 // Convert to seconds
                );
            }
        }
    }

    /**
     * Collect appointment metrics.
     */
    private function collectAppointmentMetrics(MetricsCollector $collector): void
    {
        // Get appointment statistics
        $appointmentStats = DB::table('appointments')
            ->selectRaw('status, COUNT(*) as count')
            ->whereDate('created_at', today())
            ->groupBy('status')
            ->get();

        foreach ($appointmentStats as $stat) {
            for ($i = 0; $i < $stat->count; $i++) {
                $collector->recordBooking($stat->status, 'phone');
            }
        }
    }

    /**
     * Collect call metrics.
     */
    private function collectCallMetrics(MetricsCollector $collector): void
    {
        // Get call statistics
        $callStats = DB::table('calls')
            ->selectRaw('company_id, COUNT(*) as count')
            ->whereDate('created_at', today())
            ->groupBy('company_id')
            ->get();

        foreach ($callStats as $stat) {
            for ($i = 0; $i < $stat->count; $i++) {
                $collector->recordCall('completed', $stat->company_id);
            }
        }
    }

    /**
     * Collect database performance metrics.
     */
    private function collectDatabaseMetrics(MetricsCollector $collector): void
    {
        // Get slow query count
        $slowQueries = DB::table('slow_query_log')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($slowQueries > 0) {
            for ($i = 0; $i < $slowQueries; $i++) {
                $collector->recordError('slow_query', 'warning');
            }
        }

        // Get connection pool stats if available
        try {
            $poolStats = DB::select("SHOW STATUS LIKE 'Threads_%'");

            foreach ($poolStats as $stat) {
                if ($stat->Variable_name === 'Threads_connected') {
                    $collector->updateDatabaseConnections('main', (int) $stat->Value, 0);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not collect database pool metrics', ['error' => $e->getMessage()]);
        }
    }
}
