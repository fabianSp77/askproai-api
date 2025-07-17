<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'service' => 'askproai-api'
        ]);
    }

    public function detailed(): JsonResponse
    {
        $checks = [];

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'error';
        }

        // Redis check
        try {
            Redis::ping();
            $checks['redis'] = 'ok';
        } catch (\Exception $e) {
            $checks['redis'] = 'error';
        }

        // Cache check
        try {
            Cache::put('health_check', true, 10);
            Cache::get('health_check');
            $checks['cache'] = 'ok';
        } catch (\Exception $e) {
            $checks['cache'] = 'error';
        }

        // Queue check
        try {
            $horizon = exec('php ' . base_path('artisan') . ' horizon:status');
            $checks['queue'] = str_contains($horizon, 'running') ? 'ok' : 'error';
        } catch (\Exception $e) {
            $checks['queue'] = 'error';
        }

        $allOk = !in_array('error', $checks);

        return response()->json([
            'status' => $allOk ? 'ok' : 'error',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks
        ], $allOk ? 200 : 503);
    }
}