<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => []
        ];

        // Database check
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = ['status' => 'ok', 'message' => 'Connected'];
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['database'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Redis check
        try {
            Redis::ping();
            $health['checks']['redis'] = ['status' => 'ok', 'message' => 'Connected'];
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['redis'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Disk space check
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsedPercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);
        
        if ($diskUsedPercent > 90) {
            $health['status'] = 'unhealthy';
            $health['checks']['disk'] = ['status' => 'error', 'message' => "Disk usage at {$diskUsedPercent}%"];
        } else {
            $health['checks']['disk'] = ['status' => 'ok', 'usage_percent' => $diskUsedPercent];
        }

        return response()->json($health, $health['status'] === 'healthy' ? 200 : 503);
    }
}