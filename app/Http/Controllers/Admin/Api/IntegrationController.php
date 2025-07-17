<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Services\RetellService;
use App\Services\CalcomService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IntegrationController extends Controller
{
    public function retellStatus(): JsonResponse
    {
        try {
            $retellService = app(RetellService::class);
            
            // Check last successful API call
            $lastCall = DB::table('api_call_logs')
                ->where('service', 'retell')
                ->where('status_code', 200)
                ->latest()
                ->first();
            
            // Check for recent errors
            $recentErrors = DB::table('api_call_logs')
                ->where('service', 'retell')
                ->where('status_code', '>=', 400)
                ->where('created_at', '>=', now()->subHour())
                ->count();
            
            $status = 'operational';
            if (!$lastCall || Carbon::parse($lastCall->created_at)->isBefore(now()->subHour())) {
                $status = 'unknown';
            } elseif ($recentErrors > 10) {
                $status = 'degraded';
            }
            
            return response()->json([
                'status' => $status,
                'last_successful_call' => $lastCall ? Carbon::parse($lastCall->created_at)->diffForHumans() : 'Never',
                'recent_errors' => $recentErrors,
                'webhook_url' => config('app.url') . '/api/retell/webhook',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function retellSync(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'calls');
            
            switch ($type) {
                case 'calls':
                    // Import recent calls
                    \Artisan::call('retell:import-calls', [
                        '--days' => 7,
                    ]);
                    $message = 'Call import initiated';
                    break;
                    
                case 'agents':
                    // Sync agents
                    \Artisan::call('retell:sync-agents');
                    $message = 'Agent sync completed';
                    break;
                    
                default:
                    $message = 'Unknown sync type';
            }
            
            return response()->json(['message' => $message]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function calcomStatus(): JsonResponse
    {
        try {
            $calcomService = app(CalcomService::class);
            
            // Check last successful API call
            $lastCall = DB::table('api_call_logs')
                ->where('service', 'calcom')
                ->where('status_code', 200)
                ->latest()
                ->first();
            
            // Check for recent errors
            $recentErrors = DB::table('api_call_logs')
                ->where('service', 'calcom')
                ->where('status_code', '>=', 400)
                ->where('created_at', '>=', now()->subHour())
                ->count();
            
            $status = 'operational';
            if (!$lastCall || Carbon::parse($lastCall->created_at)->isBefore(now()->subHour())) {
                $status = 'unknown';
            } elseif ($recentErrors > 10) {
                $status = 'degraded';
            }
            
            return response()->json([
                'status' => $status,
                'last_successful_call' => $lastCall ? Carbon::parse($lastCall->created_at)->diffForHumans() : 'Never',
                'recent_errors' => $recentErrors,
                'webhook_url' => config('app.url') . '/api/calcom/webhook',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function calcomSync(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'event-types');
            
            switch ($type) {
                case 'event-types':
                    // Sync event types
                    \Artisan::call('calcom:sync-event-types');
                    $message = 'Event type sync completed';
                    break;
                    
                case 'availability':
                    // Sync availability
                    \Artisan::call('calcom:sync-availability');
                    $message = 'Availability sync completed';
                    break;
                    
                default:
                    $message = 'Unknown sync type';
            }
            
            return response()->json(['message' => $message]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function recentWebhooks(Request $request): JsonResponse
    {
        $webhooks = DB::table('webhook_events')
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 50))
            ->get()
            ->map(function ($webhook) {
                return [
                    'id' => $webhook->id,
                    'source' => $webhook->source,
                    'event_type' => $webhook->event_type,
                    'status' => $webhook->status,
                    'created_at' => Carbon::parse($webhook->created_at)->format('d.m.Y H:i:s'),
                    'processed_at' => $webhook->processed_at ? Carbon::parse($webhook->processed_at)->format('d.m.Y H:i:s') : null,
                    'error' => $webhook->error_message,
                ];
            });

        return response()->json($webhooks);
    }

    public function webhookStats(): JsonResponse
    {
        $stats = [
            'total' => DB::table('webhook_events')->count(),
            'today' => DB::table('webhook_events')->whereDate('created_at', today())->count(),
            'pending' => DB::table('webhook_events')->where('status', 'pending')->count(),
            'failed' => DB::table('webhook_events')->where('status', 'failed')->count(),
            'by_source' => DB::table('webhook_events')
                ->select('source', DB::raw('count(*) as count'))
                ->groupBy('source')
                ->pluck('count', 'source'),
            'by_type' => DB::table('webhook_events')
                ->select('event_type', DB::raw('count(*) as count'))
                ->groupBy('event_type')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'event_type'),
        ];

        return response()->json($stats);
    }
}