<?php

namespace App\Services;

use App\Models\Call;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class CallQueryOptimizer
{
    /**
     * Get optimized query for call listings
     */
    public static function getOptimizedCallQuery(int $companyId): Builder
    {
        return Call::query()
            ->where('company_id', $companyId)
            ->with([
                'customer:id,name,phone,email',
                'appointment:id,starts_at,status,service_id',
                'appointment.service:id,name',
                'branch:id,name',
                'agent:id,name'
            ])
            ->select([
                'id',
                'call_id',
                'from_number',
                'to_number',
                'start_timestamp',
                'end_timestamp',
                'duration_sec',
                'call_status',
                'sentiment',
                'cost',
                'customer_id',
                'appointment_id',
                'branch_id',
                'agent_id',
                'company_id',
                'analysis',
                'audio_url',
                'recording_url',
                'public_log_url',
                'created_at',
                'updated_at'
            ]);
    }
    
    /**
     * Get cached call statistics
     */
    public static function getCachedCallStats(int $companyId): array
    {
        return Cache::remember(
            "calls.stats.company.{$companyId}",
            now()->addSeconds(30), // Cache for 30 seconds
            function () use ($companyId) {
                return [
                    'today_count' => Call::where('company_id', $companyId)
                        ->whereDate('start_timestamp', today())
                        ->count(),
                        
                    'week_count' => Call::where('company_id', $companyId)
                        ->whereBetween('start_timestamp', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ])
                        ->count(),
                        
                    'active_calls' => Call::where('company_id', $companyId)
                        ->where('call_status', 'in_progress')
                        ->count(),
                        
                    'recent_calls' => Call::where('company_id', $companyId)
                        ->where('start_timestamp', '>=', now()->subMinutes(5))
                        ->count(),
                ];
            }
        );
    }
    
    /**
     * Get recent calls with minimal data for live updates
     */
    public static function getRecentCallsForLiveUpdate(int $companyId, int $limit = 10): array
    {
        return Cache::remember(
            "calls.recent.company.{$companyId}",
            now()->addSeconds(5), // Short cache for live data
            function () use ($companyId, $limit) {
                return Call::where('company_id', $companyId)
                    ->select([
                        'id',
                        'call_id',
                        'from_number',
                        'start_timestamp',
                        'duration_sec',
                        'call_status',
                        'customer_id',
                        'appointment_id'
                    ])
                    ->with('customer:id,name')
                    ->orderBy('start_timestamp', 'desc')
                    ->limit($limit)
                    ->get()
                    ->toArray();
            }
        );
    }
    
    /**
     * Prefetch related data for performance
     */
    public static function prefetchRelatedData(Builder $query): Builder
    {
        // Use subquery to optimize counting
        $query->withCount([
            'customer as has_customer' => function ($q) {
                $q->select(DB::raw('1'));
            },
            'appointment as has_appointment' => function ($q) {
                $q->select(DB::raw('1'));
            }
        ]);
        
        return $query;
    }
    
    /**
     * Add indexes hint for better performance
     */
    public static function addIndexHints(Builder $query): Builder
    {
        // MySQL specific optimization
        if (DB::connection()->getDriverName() === 'mysql') {
            return $query->from(DB::raw('calls USE INDEX (idx_company_timestamp)'));
        }
        
        return $query;
    }
}