<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\CalcomApiRateLimiter;
use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalcomHealthController extends Controller
{
    /**
     * GET /api/health/calcom
     *
     * Comprehensive health check for Cal.com integration
     */
    public function index(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => []
        ];

        // 1. Configuration Check
        $configCheck = $this->checkConfiguration();
        $health['checks']['configuration'] = $configCheck;

        // 2. Database Check
        $databaseCheck = $this->checkDatabase();
        $health['checks']['database'] = $databaseCheck;

        // 3. Queue Worker Check
        $queueCheck = $this->checkQueueWorker();
        $health['checks']['queue'] = $queueCheck;

        // 4. Webhook Check
        $webhookCheck = $this->checkWebhook();
        $health['checks']['webhook'] = $webhookCheck;

        // 5. API Connection Check
        $apiCheck = $this->checkApiConnection();
        $health['checks']['api_connection'] = $apiCheck;

        // 6. Rate Limit Check
        $rateLimitCheck = $this->checkRateLimit();
        $health['checks']['rate_limit'] = $rateLimitCheck;

        // 7. Sync Status Check
        $syncCheck = $this->checkSyncStatus();
        $health['checks']['sync_status'] = $syncCheck;

        // Determine overall status
        $criticalChecks = ['configuration', 'database', 'queue', 'api_connection'];
        foreach ($criticalChecks as $check) {
            if ($health['checks'][$check]['status'] === 'unhealthy') {
                $health['status'] = 'unhealthy';
                break;
            }
        }

        if ($health['status'] === 'healthy') {
            foreach ($health['checks'] as $check) {
                if ($check['status'] === 'degraded') {
                    $health['status'] = 'degraded';
                    break;
                }
            }
        }

        // Add summary
        $health['summary'] = $this->generateSummary($health['checks']);

        return response()->json($health, $health['status'] === 'unhealthy' ? 503 : 200);
    }

    private function checkConfiguration(): array
    {
        try {
            $apiKey = config('services.calcom.api_key');
            $baseUrl = config('services.calcom.base_url');
            $webhookSecret = config('services.calcom.webhook_secret');

            $status = ($apiKey && $baseUrl && $webhookSecret) ? 'healthy' : 'unhealthy';

            return [
                'status' => $status,
                'message' => $status === 'healthy'
                    ? 'All configuration values present'
                    : 'Missing configuration values',
                'details' => [
                    'api_key' => $apiKey ? 'configured' : 'missing',
                    'base_url' => $baseUrl ? 'configured' : 'missing',
                    'webhook_secret' => $webhookSecret ? 'configured' : 'missing',
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Configuration check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkDatabase(): array
    {
        try {
            $serviceCount = Service::count();
            $syncedCount = Service::where('sync_status', 'synced')->count();
            $errorCount = Service::where('sync_status', 'error')->count();

            $status = 'healthy';
            if ($errorCount > 0) {
                $status = 'degraded';
            }

            return [
                'status' => $status,
                'message' => "Database connection successful",
                'details' => [
                    'total_services' => $serviceCount,
                    'synced_services' => $syncedCount,
                    'error_services' => $errorCount,
                    'sync_percentage' => $serviceCount > 0
                        ? round(($syncedCount / $serviceCount) * 100, 2) . '%'
                        : '0%'
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkQueueWorker(): array
    {
        try {
            $queuedJobs = DB::table('jobs')
                ->where('queue', 'calcom-sync')
                ->count();

            $failedJobs = DB::table('failed_jobs')
                ->where('queue', 'calcom-sync')
                ->where('failed_at', '>', now()->subHours(24))
                ->count();

            // Check if worker is processing
            $lastProcessed = Cache::get('calcom_queue_last_processed');
            $workerActive = $lastProcessed && Carbon::parse($lastProcessed)->gt(now()->subMinutes(5));

            $status = 'healthy';
            if ($failedJobs > 5) {
                $status = 'degraded';
            }
            if (!$workerActive && $queuedJobs > 0) {
                $status = 'unhealthy';
            }

            return [
                'status' => $status,
                'message' => $workerActive ? 'Queue worker active' : 'Queue worker may be inactive',
                'details' => [
                    'queued_jobs' => $queuedJobs,
                    'failed_jobs_24h' => $failedJobs,
                    'worker_active' => $workerActive,
                    'last_processed' => $lastProcessed ?? 'unknown'
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Queue check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkWebhook(): array
    {
        try {
            // Check last webhook received
            $lastWebhook = Cache::get('calcom_last_webhook_received');
            $webhookActive = $lastWebhook && Carbon::parse($lastWebhook)->gt(now()->subHours(24));

            // Check webhook errors
            $webhookErrors = Cache::get('calcom_webhook_errors_24h', 0);

            $status = 'healthy';
            if ($webhookErrors > 10) {
                $status = 'degraded';
            }
            if ($webhookErrors > 50) {
                $status = 'unhealthy';
            }

            return [
                'status' => $status,
                'message' => $webhookActive ? 'Webhooks active' : 'No webhooks in last 24h',
                'details' => [
                    'last_received' => $lastWebhook ?? 'never',
                    'errors_24h' => $webhookErrors,
                    'webhook_active' => $webhookActive
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Webhook check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkApiConnection(): array
    {
        try {
            $calcomService = new CalcomService();

            if (!$calcomService->isConfigured()) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Cal.com service not configured'
                ];
            }

            $result = $calcomService->testConnection();

            return [
                'status' => $result['success'] ? 'healthy' : 'unhealthy',
                'message' => $result['message'],
                'details' => [
                    'api_reachable' => $result['success'],
                    'user' => $result['data']['user']['email'] ?? null
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'API connection check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkRateLimit(): array
    {
        try {
            $rateLimiter = new CalcomApiRateLimiter();
            $remaining = $rateLimiter->getRemainingRequests();

            $status = 'healthy';
            if ($remaining < 20) {
                $status = 'degraded';
            }
            if ($remaining < 5) {
                $status = 'unhealthy';
            }

            return [
                'status' => $status,
                'message' => "Rate limit: {$remaining}/60 requests remaining",
                'details' => [
                    'remaining_requests' => $remaining,
                    'max_requests' => 60,
                    'percentage_available' => round(($remaining / 60) * 100, 2) . '%'
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Rate limit check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkSyncStatus(): array
    {
        try {
            $lastSync = Service::whereNotNull('last_calcom_sync')
                ->orderBy('last_calcom_sync', 'desc')
                ->value('last_calcom_sync');

            $syncAge = $lastSync ? Carbon::parse($lastSync)->diffInMinutes(now()) : 999999;

            // Check sync success rate (last 24h)
            $recentSyncs = Service::where('last_calcom_sync', '>', now()->subDay())
                ->select('sync_status')
                ->get();

            $successRate = $recentSyncs->isNotEmpty()
                ? round(($recentSyncs->where('sync_status', 'synced')->count() / $recentSyncs->count()) * 100, 2)
                : 0;

            $status = 'healthy';
            if ($syncAge > 60 || $successRate < 80) {
                $status = 'degraded';
            }
            if ($syncAge > 180 || $successRate < 50) {
                $status = 'unhealthy';
            }

            return [
                'status' => $status,
                'message' => "Last sync {$syncAge} minutes ago",
                'details' => [
                    'last_sync' => $lastSync ?? 'never',
                    'minutes_since_sync' => $syncAge,
                    'success_rate_24h' => $successRate . '%',
                    'syncs_24h' => $recentSyncs->count()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Sync status check failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function generateSummary(array $checks): array
    {
        $summary = [
            'healthy' => 0,
            'degraded' => 0,
            'unhealthy' => 0
        ];

        foreach ($checks as $check) {
            $summary[$check['status']]++;
        }

        return $summary;
    }
}