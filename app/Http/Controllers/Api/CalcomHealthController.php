<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\CalcomHealthCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalcomHealthController extends Controller
{
    private CalcomHealthCheck $healthCheck;

    public function __construct(CalcomHealthCheck $healthCheck)
    {
        $this->healthCheck = $healthCheck;
    }

    /**
     * Quick health check endpoint (cached, fast)
     * GET /api/health/calcom
     */
    public function index(): JsonResponse
    {
        $result = CalcomHealthCheck::quickCheck();

        $statusCode = match($result['status']) {
            'healthy' => 200,
            'warning' => 200,
            'critical', 'unhealthy' => 503,
            default => 200
        };

        return response()->json($result, $statusCode);
    }

    /**
     * Detailed health check (comprehensive, slower)
     * GET /api/health/calcom/detailed
     */
    public function detailed(Request $request): JsonResponse
    {
        // Optional: Add authentication for detailed health check
        // $this->authorize('viewHealthCheck', CalcomHealthCheck::class);

        $result = $this->healthCheck->check();

        $statusCode = match($result['status']) {
            'healthy' => 200,
            'warning' => 200,
            'critical' => 503,
            default => 200
        };

        return response()->json($result, $statusCode);
    }

    /**
     * Get health metrics for monitoring dashboard
     * GET /api/health/calcom/metrics
     */
    public function metrics(): JsonResponse
    {
        $result = $this->healthCheck->check();

        return response()->json([
            'metrics' => $result['metrics'],
            'status' => $result['status'],
            'timestamp' => $result['timestamp']
        ]);
    }

    /**
     * Trigger manual health check and alert
     * POST /api/health/calcom/check
     */
    public function runCheck(): JsonResponse
    {
        $result = $this->healthCheck->check();

        // Send alerts if critical issues found
        $this->healthCheck->alertIfCritical();

        return response()->json([
            'message' => 'Health check completed',
            'status' => $result['status'],
            'issues_found' => count($result['issues']),
            'timestamp' => $result['timestamp']
        ]);
    }
}