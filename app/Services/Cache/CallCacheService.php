<?php

namespace App\Services\Cache;

use App\Models\Call;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class CallCacheService
{
    // Cache TTL values in seconds
    private const TTL_LIST = 300; // 5 minutes for list views
    private const TTL_STATS_DAILY = 3600; // 1 hour for daily stats
    private const TTL_DETAIL = 1800; // 30 minutes for detail views
    private const TTL_TRANSCRIPT = 86400; // 24 hours for transcripts
    private const TTL_FILTERS = 600; // 10 minutes for filter options
    private const TTL_ANALYTICS = 1800; // 30 minutes for analytics data
    
    /**
     * Get cached call list with automatic refresh
     */
    public function getCallsList(int $companyId, int $page = 1, array $filters = [], int $perPage = 25): ?LengthAwarePaginator
    {
        $cacheKey = $this->getListCacheKey($companyId, $page, $filters, $perPage);
        
        return Cache::remember($cacheKey, self::TTL_LIST, function () use ($companyId, $filters, $page, $perPage) {
            $query = Call::where('company_id', $companyId);
            
            // Apply filters
            $this->applyFilters($query, $filters);
            
            // Optimize query with eager loading
            $query->with(['customer', 'appointment', 'staff', 'branch'])
                  ->orderBy('created_at', 'desc');
            
            return $query->paginate($perPage, ['*'], 'page', $page);
        });
    }
    
    /**
     * Get cached daily statistics
     */
    public function getDailyStats(int $companyId, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $cacheKey = "calls:stats:{$companyId}:daily:{$date->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, self::TTL_STATS_DAILY, function () use ($companyId, $date) {
            $stats = Call::where('company_id', $companyId)
                ->whereDate('created_at', $date)
                ->selectRaw('
                    COUNT(*) as total_calls,
                    COUNT(CASE WHEN appointment_made = 1 THEN 1 END) as appointments_made,
                    AVG(duration_sec) as avg_duration,
                    SUM(cost_cents) / 100 as total_cost,
                    COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
                    COUNT(CASE WHEN status = "failed" THEN 1 END) as failed,
                    AVG(sentiment_score) as avg_sentiment,
                    COUNT(CASE WHEN duration_sec > 300 THEN 1 END) as long_calls,
                    COUNT(DISTINCT customer_id) as unique_customers
                ')
                ->first();
            
            return [
                'date' => $date->format('Y-m-d'),
                'total_calls' => $stats->total_calls ?? 0,
                'appointments_made' => $stats->appointments_made ?? 0,
                'conversion_rate' => $stats->total_calls > 0 
                    ? round(($stats->appointments_made / $stats->total_calls) * 100, 1) 
                    : 0,
                'avg_duration_minutes' => round(($stats->avg_duration ?? 0) / 60, 1),
                'total_cost' => round($stats->total_cost ?? 0, 2),
                'completed' => $stats->completed ?? 0,
                'failed' => $stats->failed ?? 0,
                'avg_sentiment' => round($stats->avg_sentiment ?? 0, 2),
                'long_calls' => $stats->long_calls ?? 0,
                'unique_customers' => $stats->unique_customers ?? 0,
            ];
        });
    }
    
    /**
     * Get cached call details
     */
    public function getCallDetail(string $callId): ?Call
    {
        $cacheKey = "calls:detail:{$callId}";
        
        return Cache::remember($cacheKey, self::TTL_DETAIL, function () use ($callId) {
            return Call::with([
                'customer',
                'appointment',
                'appointment.service',
                'appointment.staff',
                'branch',
                'company',
                'charges'
            ])
            ->find($callId);
        });
    }
    
    /**
     * Get cached transcript
     */
    public function getTranscript(string $callId): ?string
    {
        $cacheKey = "calls:transcript:{$callId}";
        
        return Cache::remember($cacheKey, self::TTL_TRANSCRIPT, function () use ($callId) {
            $call = Call::select('transcript')->find($callId);
            return $call ? $call->transcript : null;
        });
    }
    
    /**
     * Get cached filter options for dropdowns
     */
    public function getFilterOptions(int $companyId): array
    {
        $cacheKey = "calls:filters:{$companyId}";
        
        return Cache::remember($cacheKey, self::TTL_FILTERS, function () use ($companyId) {
            return [
                'statuses' => Call::where('company_id', $companyId)
                    ->distinct()
                    ->pluck('status')
                    ->filter()
                    ->values()
                    ->toArray(),
                    
                'branches' => \App\Models\Branch::where('company_id', $companyId)
                    ->pluck('name', 'id')
                    ->toArray(),
                    
                'staff' => \App\Models\Staff::where('company_id', $companyId)
                    ->pluck('name', 'id')
                    ->toArray(),
                    
                'sentiments' => ['positive', 'neutral', 'negative'],
                
                'duration_ranges' => [
                    '0-60' => 'Unter 1 Minute',
                    '60-300' => '1-5 Minuten',
                    '300-600' => '5-10 Minuten',
                    '600+' => 'Über 10 Minuten',
                ],
            ];
        });
    }
    
    /**
     * Get cached analytics data for charts
     */
    public function getAnalyticsData(int $companyId, string $period = '24h'): array
    {
        $cacheKey = "calls:analytics:{$companyId}:{$period}";
        
        return Cache::remember($cacheKey, self::TTL_ANALYTICS, function () use ($companyId, $period) {
            $data = [];
            
            switch ($period) {
                case '24h':
                    $data = $this->get24HourAnalytics($companyId);
                    break;
                case '7d':
                    $data = $this->get7DayAnalytics($companyId);
                    break;
                case '30d':
                    $data = $this->get30DayAnalytics($companyId);
                    break;
                default:
                    $data = $this->get24HourAnalytics($companyId);
            }
            
            return $data;
        });
    }
    
    /**
     * Invalidate cache for a specific call
     */
    public function invalidateCall(string $callId, int $companyId): void
    {
        // Clear specific call cache
        Cache::forget("calls:detail:{$callId}");
        Cache::forget("calls:transcript:{$callId}");
        
        // Clear list caches (all pages)
        $this->invalidateListCache($companyId);
        
        // Clear stats for today
        $today = Carbon::today()->format('Y-m-d');
        Cache::forget("calls:stats:{$companyId}:daily:{$today}");
        
        // Clear analytics
        Cache::forget("calls:analytics:{$companyId}:24h");
        Cache::forget("calls:analytics:{$companyId}:7d");
        Cache::forget("calls:analytics:{$companyId}:30d");
    }
    
    /**
     * Invalidate all caches for a company
     */
    public function invalidateCompany(int $companyId): void
    {
        // Use Redis pattern deletion for efficiency
        $patterns = [
            "calls:list:{$companyId}:*",
            "calls:stats:{$companyId}:*",
            "calls:filters:{$companyId}",
            "calls:analytics:{$companyId}:*",
        ];
        
        foreach ($patterns as $pattern) {
            $this->deleteByPattern($pattern);
        }
    }
    
    /**
     * Warm up cache for a company
     */
    public function warmUpCache(int $companyId): void
    {
        // Pre-load commonly accessed data
        $this->getDailyStats($companyId);
        $this->getFilterOptions($companyId);
        $this->getAnalyticsData($companyId, '24h');
        
        // Pre-load first page of calls
        $this->getCallsList($companyId, 1, [], 25);
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $stats = [];
        
        // Get Redis info if available
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Redis::connection();
            $info = $redis->info();
            
            $stats = [
                'used_memory' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'instantaneous_ops_per_sec' => $info['instantaneous_ops_per_sec'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => isset($info['keyspace_hits']) && isset($info['keyspace_misses']) 
                    ? round(($info['keyspace_hits'] / ($info['keyspace_hits'] + $info['keyspace_misses'])) * 100, 2) 
                    : 0,
            ];
        }
        
        return $stats;
    }
    
    /**
     * Generate cache key for list views
     */
    private function getListCacheKey(int $companyId, int $page, array $filters, int $perPage): string
    {
        $filterHash = md5(serialize($filters));
        return "calls:list:{$companyId}:{$page}:{$perPage}:{$filterHash}";
    }
    
    /**
     * Apply filters to query
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        
        if (!empty($filters['staff_id'])) {
            $query->whereHas('appointment', function ($q) use ($filters) {
                $q->where('staff_id', $filters['staff_id']);
            });
        }
        
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('call_id', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }
        
        if (!empty($filters['appointment_made'])) {
            $query->where('appointment_made', $filters['appointment_made'] === 'yes' ? 1 : 0);
        }
        
        if (!empty($filters['sentiment'])) {
            $query->where('sentiment', $filters['sentiment']);
        }
        
        if (!empty($filters['duration_range'])) {
            switch ($filters['duration_range']) {
                case '0-60':
                    $query->whereBetween('duration_sec', [0, 60]);
                    break;
                case '60-300':
                    $query->whereBetween('duration_sec', [60, 300]);
                    break;
                case '300-600':
                    $query->whereBetween('duration_sec', [300, 600]);
                    break;
                case '600+':
                    $query->where('duration_sec', '>', 600);
                    break;
            }
        }
    }
    
    /**
     * Get 24-hour analytics data
     */
    private function get24HourAnalytics(int $companyId): array
    {
        $now = Carbon::now();
        $data = [];
        
        for ($i = 23; $i >= 0; $i--) {
            $hour = $now->copy()->subHours($i);
            $hourData = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [
                    $hour->copy()->startOfHour(),
                    $hour->copy()->endOfHour()
                ])
                ->selectRaw('
                    COUNT(*) as total,
                    COUNT(CASE WHEN appointment_made = 1 THEN 1 END) as appointments,
                    AVG(duration_sec) as avg_duration
                ')
                ->first();
            
            $data['labels'][] = $hour->format('H:00');
            $data['calls'][] = $hourData->total ?? 0;
            $data['appointments'][] = $hourData->appointments ?? 0;
            $data['avg_duration'][] = round(($hourData->avg_duration ?? 0) / 60, 1);
        }
        
        return $data;
    }
    
    /**
     * Get 7-day analytics data
     */
    private function get7DayAnalytics(int $companyId): array
    {
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayData = Call::where('company_id', $companyId)
                ->whereDate('created_at', $date)
                ->selectRaw('
                    COUNT(*) as total,
                    COUNT(CASE WHEN appointment_made = 1 THEN 1 END) as appointments,
                    AVG(duration_sec) as avg_duration,
                    SUM(cost_cents) / 100 as total_cost
                ')
                ->first();
            
            $data['labels'][] = $date->format('d.m');
            $data['calls'][] = $dayData->total ?? 0;
            $data['appointments'][] = $dayData->appointments ?? 0;
            $data['avg_duration'][] = round(($dayData->avg_duration ?? 0) / 60, 1);
            $data['cost'][] = round($dayData->total_cost ?? 0, 2);
        }
        
        return $data;
    }
    
    /**
     * Get 30-day analytics data
     */
    private function get30DayAnalytics(int $companyId): array
    {
        $data = [];
        
        // Group by week for 30 days
        for ($week = 3; $week >= 0; $week--) {
            $startDate = Carbon::today()->subWeeks($week)->startOfWeek();
            $endDate = $startDate->copy()->endOfWeek();
            
            $weekData = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total,
                    COUNT(CASE WHEN appointment_made = 1 THEN 1 END) as appointments,
                    AVG(duration_sec) as avg_duration,
                    SUM(cost_cents) / 100 as total_cost
                ')
                ->first();
            
            $data['labels'][] = 'KW ' . $startDate->weekOfYear;
            $data['calls'][] = $weekData->total ?? 0;
            $data['appointments'][] = $weekData->appointments ?? 0;
            $data['avg_duration'][] = round(($weekData->avg_duration ?? 0) / 60, 1);
            $data['cost'][] = round($weekData->total_cost ?? 0, 2);
        }
        
        return $data;
    }
    
    /**
     * Delete cache keys by pattern
     */
    private function deleteByPattern(string $pattern): void
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $redis = Redis::connection();
            $keys = $redis->keys($pattern);
            
            if (!empty($keys)) {
                $redis->del($keys);
            }
        }
    }
    
    /**
     * Invalidate list cache for a company
     */
    private function invalidateListCache(int $companyId): void
    {
        $this->deleteByPattern("calls:list:{$companyId}:*");
    }
}