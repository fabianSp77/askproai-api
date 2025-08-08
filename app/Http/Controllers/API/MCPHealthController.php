<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MCP\RetellMCPServer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MCPHealthController extends Controller
{
    public function health(): JsonResponse
    {
        $startTime = microtime(true);
        $health = [
            'service' => 'Retell MCP Server',
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => []
        ];

        // Database connectivity check
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = ['status' => 'healthy', 'response_time_ms' => 0];
        } catch (\Exception $e) {
            $health['checks']['database'] = [
                'status' => 'unhealthy', 
                'error' => $e->getMessage()
            ];
            $health['status'] = 'unhealthy';
        }

        // Cache connectivity check
        try {
            Cache::put('health_check', time(), 10);
            $retrieved = Cache::get('health_check');
            $health['checks']['cache'] = [
                'status' => $retrieved ? 'healthy' : 'unhealthy'
            ];
        } catch (\Exception $e) {
            $health['checks']['cache'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'unhealthy';
        }

        // MCP Server functionality check
        try {
            $mcpServer = app(RetellMCPServer::class);
            $testResult = $mcpServer->getAvailableSlots([
                'company_id' => 1,
                'date' => now()->format('Y-m-d'),
                'branch_id' => 1
            ]);
            
            $health['checks']['mcp_tools'] = [
                'status' => !isset($testResult['error']) ? 'healthy' : 'degraded',
                'test_tool' => 'getAvailableSlots'
            ];
        } catch (\Exception $e) {
            $health['checks']['mcp_tools'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
            $health['status'] = 'unhealthy';
        }

        // Performance metrics
        $duration = (microtime(true) - $startTime) * 1000;
        $health['response_time_ms'] = round($duration, 2);

        return response()->json($health)
            ->header('X-Health-Check-Duration', round($duration, 2));
    }
}