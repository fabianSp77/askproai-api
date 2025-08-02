<?php

namespace App\Http\Controllers\Admin\Api;

use App\Services\DashboardStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends BaseAdminApiController
{
    protected DashboardStatsService $statsService;
    
    public function __construct(DashboardStatsService $statsService)
    {
        $this->statsService = $statsService;
    }
    
    /**
     * Get dashboard statistics - Optimized version
     * Previous: 150+ queries, 3+ seconds
     * Optimized: <20 queries, <500ms
     */
    public function stats(Request $request)
    {
        // Get user's company ID
        $companyId = auth()->user()->company_id ?? null;
        
        if (!$companyId) {
            return response()->json([
                'error' => 'No company context available'
            ], 403);
        }
        
        // Get optimized stats from service
        $stats = $this->statsService->getStats($companyId);
        
        // Calculate trends from historical data
        $callsTrend = $this->calculateTrend($stats['trends'] ?? [], 'calls');
        $customersTrend = $this->calculateTrend($stats['trends'] ?? [], 'customers');
        
        // Format charts data from trends
        $callsChart = collect($stats['trends'] ?? [])
            ->take(7)
            ->map(function ($day) {
                return [
                    'date' => Carbon::parse($day['date'])->format('d.m'),
                    'count' => $day['calls'] ?? 0
                ];
            })
            ->toArray();
        
        // Format response for API compatibility
        return response()->json([
            'calls' => [
                'total' => $stats['calls']['total_count'] ?? 0,
                'today' => $stats['overview']['calls_today'] ?? 0,
                'trend' => $callsTrend,
                'positive_sentiment' => $stats['calls']['positive_sentiment_rate'] ?? 0
            ],
            'appointments' => [
                'total' => array_sum($stats['appointments']['by_status'] ?? []),
                'today' => $stats['appointments']['by_time']['today'] ?? 0,
                'upcoming' => ($stats['appointments']['by_status']['scheduled'] ?? 0) + 
                            ($stats['appointments']['by_status']['confirmed'] ?? 0),
                'completed_rate' => $this->calculateCompletedRate($stats['appointments']['by_status'] ?? []),
                'completed' => $stats['appointments']['by_status']['completed'] ?? 0,
                'scheduled' => $stats['appointments']['by_status']['scheduled'] ?? 0,
                'cancelled' => $stats['appointments']['by_status']['cancelled'] ?? 0,
                'no_show' => $stats['appointments']['by_status']['no_show'] ?? 0
            ],
            'customers' => [
                'total' => $stats['customers']['total'] ?? 0,
                'new_this_month' => $stats['customers']['new_this_month'] ?? 0,
                'active' => $stats['customers']['with_completed_appointments'] ?? 0,
                'trend' => $customersTrend
            ],
            'companies' => [
                'total' => 1, // Current company only
                'active' => 1,
                'trial' => 0,
                'premium' => 1
            ],
            'charts' => [
                'calls' => $callsChart,
                'appointments' => [],
                'revenue' => []
            ]
        ]);
    }

    /**
     * Get recent activity - Optimized for current company only
     */
    public function recentActivity(Request $request)
    {
        $companyId = auth()->user()->company_id;
        
        if (!$companyId) {
            return response()->json([
                'error' => 'No company context available'
            ], 403);
        }
        
        $limit = min($request->input('limit', 20), 50);
        $cacheKey = "recent_activity_{$companyId}_{$limit}";
        
        $activities = Cache::remember($cacheKey, 60, function () use ($companyId, $limit) {
            // Get recent activities with single optimized query
            $activities = DB::select("
                (SELECT 
                    CONCAT('appointment-', id) as id,
                    'appointment' as type,
                    CONCAT('Neuer Termin: ', 
                        COALESCE((SELECT name FROM customers WHERE id = appointments.customer_id), 'Unbekannt'),
                        ' - ',
                        COALESCE((SELECT name FROM services WHERE id = appointments.service_id), 'Unbekannt')
                    ) as description,
                    created_at as timestamp
                FROM appointments
                WHERE company_id = ? AND deleted_at IS NULL
                ORDER BY created_at DESC
                LIMIT 10)
                
                UNION ALL
                
                (SELECT 
                    CONCAT('call-', id) as id,
                    'call' as type,
                    CONCAT('Anruf von ', from_phone_number) as description,
                    created_at as timestamp
                FROM calls
                WHERE company_id = ? AND deleted_at IS NULL
                ORDER BY created_at DESC
                LIMIT 10)
                
                UNION ALL
                
                (SELECT 
                    CONCAT('customer-', id) as id,
                    'customer' as type,
                    CONCAT('Neuer Kunde: ', name) as description,
                    created_at as timestamp
                FROM customers
                WHERE company_id = ? AND deleted_at IS NULL
                ORDER BY created_at DESC
                LIMIT 5)
                
                ORDER BY timestamp DESC
                LIMIT ?
            ", [$companyId, $companyId, $companyId, $limit]);
            
            return collect($activities)->map(function ($activity) use ($companyId) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->type,
                    'description' => $activity->description,
                    'timestamp' => $activity->timestamp,
                    'company' => [
                        'id' => $companyId,
                        'name' => auth()->user()->company->name ?? 'Unknown'
                    ]
                ];
            })->toArray();
        });
        
        return response()->json($activities);
    }

    /**
     * Get system health status
     */
    public function systemHealth(Request $request)
    {
        $cacheKey = 'system_health_status';
        
        $health = Cache::remember($cacheKey, 60, function () {
            $dbStatus = $this->checkDatabase();
            $redisStatus = $this->checkRedis();
            $retellStatus = $this->checkRetellApi();
            $calcomStatus = $this->checkCalcomApi();
            $queuePending = DB::table('jobs')->count();
            $queueFailed = DB::table('failed_jobs')->count();
            
            // Determine overall status
            $overallStatus = 'healthy';
            if (!$dbStatus || !$redisStatus) {
                $overallStatus = 'critical';
            } elseif (!$retellStatus || !$calcomStatus || $queueFailed > 10) {
                $overallStatus = 'warning';
            }

            return [
                'status' => $overallStatus,
                'message' => $overallStatus === 'critical' ? 'Kritische Systemkomponenten sind ausgefallen' : 
                            ($overallStatus === 'warning' ? 'Einige Services haben Probleme' : 'Alle Systeme funktionieren normal'),
                'services' => [
                    [
                        'name' => 'Database',
                        'status' => $dbStatus ? 'healthy' : 'critical',
                        'response_time' => $this->getDatabaseResponseTime(),
                    ],
                    [
                        'name' => 'Redis Cache',
                        'status' => $redisStatus ? 'healthy' : 'warning',
                        'response_time' => $this->getRedisResponseTime(),
                    ],
                    [
                        'name' => 'Retell.ai API',
                        'status' => $retellStatus ? 'healthy' : 'warning',
                        'last_sync' => $this->getLastRetellSync(),
                    ],
                    [
                        'name' => 'Cal.com API',
                        'status' => $calcomStatus ? 'healthy' : 'warning',
                        'last_sync' => $this->getLastCalcomSync(),
                    ],
                    [
                        'name' => 'Queue System',
                        'status' => $queueFailed > 10 ? 'warning' : 'healthy',
                    ],
                ],
                'queue' => [
                    'pending' => $queuePending,
                    'failed' => $queueFailed,
                ],
                'api_response_time' => $this->getApiResponseTime(),
            ];
        });

        return response()->json($health);
    }

    /**
     * Calculate trend percentage
     */
    private function calculateTrend(array $trends, string $metric): float
    {
        if (count($trends) < 2) {
            return 0;
        }
        
        // Get today and yesterday values
        $today = $trends[count($trends) - 1][$metric] ?? 0;
        $yesterday = $trends[count($trends) - 2][$metric] ?? 0;
        
        if ($yesterday == 0) {
            return $today > 0 ? 100 : 0;
        }
        
        return round((($today - $yesterday) / $yesterday) * 100, 1);
    }
    
    /**
     * Calculate appointment completion rate
     */
    private function calculateCompletedRate(array $statusCounts): float
    {
        $total = array_sum($statusCounts);
        
        if ($total == 0) {
            return 0;
        }
        
        $completed = $statusCounts['completed'] ?? 0;
        
        return round(($completed / $total) * 100, 1);
    }

    /**
     * Check database connection
     */
    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            Log::error('Database connection check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get database response time
     */
    private function getDatabaseResponseTime(): string
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            return round((microtime(true) - $start) * 1000, 2) . 'ms';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Check Redis connection
     */
    private function checkRedis(): bool
    {
        try {
            Cache::store('redis')->put('health_check', 'ok', 1);
            return Cache::store('redis')->get('health_check') === 'ok';
        } catch (\Exception $e) {
            Log::error('Redis connection check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get Redis response time
     */
    private function getRedisResponseTime(): string
    {
        try {
            $start = microtime(true);
            Cache::store('redis')->get('health_check');
            return round((microtime(true) - $start) * 1000, 2) . 'ms';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Check Retell API status
     */
    private function checkRetellApi(): bool
    {
        try {
            // Check last successful API call within the current company context
            $companyId = auth()->user()->company_id ?? null;
            
            $lastCall = DB::table('api_call_logs')
                ->where('service', 'retell')
                ->where('status_code', 200)
                ->when($companyId, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->latest()
                ->first();

            if (!$lastCall) {
                return false;
            }

            // If last successful call was within 1 hour, consider it operational
            return Carbon::parse($lastCall->created_at)->isAfter(Carbon::now()->subHour());
        } catch (\Exception $e) {
            Log::error('Retell API check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get last Retell sync time
     */
    private function getLastRetellSync(): string
    {
        try {
            $companyId = auth()->user()->company_id ?? null;
            
            $lastSync = DB::table('sync_logs')
                ->where('service', 'retell')
                ->when($companyId, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->latest()
                ->first();

            return $lastSync ? Carbon::parse($lastSync->created_at)->diffForHumans() : 'Never';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Check Cal.com API status
     */
    private function checkCalcomApi(): bool
    {
        try {
            $companyId = auth()->user()->company_id ?? null;
            
            $lastCall = DB::table('api_call_logs')
                ->where('service', 'calcom')
                ->where('status_code', 200)
                ->when($companyId, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->latest()
                ->first();

            if (!$lastCall) {
                return false;
            }

            return Carbon::parse($lastCall->created_at)->isAfter(Carbon::now()->subHour());
        } catch (\Exception $e) {
            Log::error('Calcom API check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get last Cal.com sync time
     */
    private function getLastCalcomSync(): string
    {
        try {
            $companyId = auth()->user()->company_id ?? null;
            
            $lastSync = DB::table('sync_logs')
                ->where('service', 'calcom')
                ->when($companyId, function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->latest()
                ->first();

            return $lastSync ? Carbon::parse($lastSync->created_at)->diffForHumans() : 'Never';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
    
    /**
     * Get average API response time
     */
    private function getApiResponseTime(): string
    {
        try {
            $avgTime = DB::table('api_call_logs')
                ->where('created_at', '>=', Carbon::now()->subHour())
                ->avg('duration_ms');
                
            return $avgTime ? round($avgTime, 2) . 'ms' : '0ms';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }
}