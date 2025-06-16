<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryCache
{
    /**
     * Cache TTL for different query types (in seconds)
     */
    private $cacheTTL = [
        'stats' => 300,        // 5 minutes for statistics
        'counts' => 180,       // 3 minutes for counts
        'aggregations' => 600, // 10 minutes for complex aggregations
        'reports' => 1800,     // 30 minutes for reports
    ];

    /**
     * Cache appointment statistics
     */
    public function getAppointmentStats($companyId, $dateRange = 'month')
    {
        $cacheKey = "appointment_stats:{$companyId}:{$dateRange}";
        
        return Cache::remember($cacheKey, $this->cacheTTL['stats'], function () use ($companyId, $dateRange) {
            $startDate = $this->getStartDate($dateRange);
            
            return [
                'total' => DB::table('appointments')
                    ->where('company_id', $companyId)
                    ->where('starts_at', '>=', $startDate)
                    ->count(),
                    
                'by_status' => DB::table('appointments')
                    ->where('company_id', $companyId)
                    ->where('starts_at', '>=', $startDate)
                    ->groupBy('status')
                    ->selectRaw('status, COUNT(*) as count')
                    ->pluck('count', 'status')
                    ->toArray(),
                    
                'by_branch' => DB::table('appointments')
                    ->join('branches', 'appointments.branch_id', '=', 'branches.id')
                    ->where('appointments.company_id', $companyId)
                    ->where('appointments.starts_at', '>=', $startDate)
                    ->groupBy('branches.id', 'branches.name')
                    ->selectRaw('branches.name, COUNT(*) as count')
                    ->pluck('count', 'name')
                    ->toArray(),
                    
                'by_staff' => DB::table('appointments')
                    ->join('staff', 'appointments.staff_id', '=', 'staff.id')
                    ->where('appointments.company_id', $companyId)
                    ->where('appointments.starts_at', '>=', $startDate)
                    ->groupBy('staff.id', 'staff.name')
                    ->selectRaw('staff.name, COUNT(*) as count')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->pluck('count', 'name')
                    ->toArray(),
                    
                'revenue' => DB::table('appointments')
                    ->where('company_id', $companyId)
                    ->where('starts_at', '>=', $startDate)
                    ->where('status', 'completed')
                    ->sum('price'),
                    
                'daily_distribution' => $this->getDailyDistribution($companyId, $startDate),
                'hourly_distribution' => $this->getHourlyDistribution($companyId, $startDate),
            ];
        });
    }

    /**
     * Cache customer metrics
     */
    public function getCustomerMetrics($companyId)
    {
        $cacheKey = "customer_metrics:{$companyId}";
        
        return Cache::remember($cacheKey, $this->cacheTTL['aggregations'], function () use ($companyId) {
            return [
                'total' => DB::table('customers')
                    ->where('company_id', $companyId)
                    ->count(),
                    
                'new_this_month' => DB::table('customers')
                    ->where('company_id', $companyId)
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count(),
                    
                'active' => DB::table('customers')
                    ->where('company_id', $companyId)
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('appointments')
                            ->whereColumn('appointments.customer_id', 'customers.id')
                            ->where('appointments.status', '!=', 'cancelled')
                            ->where('appointments.starts_at', '>=', now()->subDays(90));
                    })
                    ->count(),
                    
                'top_customers' => DB::table('customers')
                    ->join('appointments', 'customers.id', '=', 'appointments.customer_id')
                    ->where('customers.company_id', $companyId)
                    ->where('appointments.status', 'completed')
                    ->groupBy('customers.id', 'customers.name')
                    ->selectRaw('customers.name, COUNT(*) as appointment_count, SUM(appointments.price) as total_revenue')
                    ->orderByDesc('total_revenue')
                    ->limit(10)
                    ->get(),
                    
                'retention_rate' => $this->calculateRetentionRate($companyId),
                'lifetime_value' => $this->calculateAverageLifetimeValue($companyId),
            ];
        });
    }

    /**
     * Cache call statistics
     */
    public function getCallStats($companyId, $days = 30)
    {
        $cacheKey = "call_stats:{$companyId}:{$days}";
        
        return Cache::remember($cacheKey, $this->cacheTTL['stats'], function () use ($companyId, $days) {
            $startDate = now()->subDays($days);
            
            return [
                'total' => DB::table('calls')
                    ->where('company_id', $companyId)
                    ->where('created_at', '>=', $startDate)
                    ->count(),
                    
                'successful' => DB::table('calls')
                    ->where('company_id', $companyId)
                    ->where('created_at', '>=', $startDate)
                    ->where('successful', true)
                    ->count(),
                    
                'with_appointments' => DB::table('calls')
                    ->where('company_id', $companyId)
                    ->where('created_at', '>=', $startDate)
                    ->whereNotNull('appointment_id')
                    ->count(),
                    
                'avg_duration' => DB::table('calls')
                    ->where('company_id', $companyId)
                    ->where('created_at', '>=', $startDate)
                    ->avg('duration_sec'),
                    
                'total_cost' => DB::table('calls')
                    ->where('company_id', $companyId)
                    ->where('created_at', '>=', $startDate)
                    ->sum('cost'),
                    
                'by_hour' => DB::table('calls')
                    ->where('company_id', $companyId)
                    ->where('created_at', '>=', $startDate)
                    ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                    ->groupBy('hour')
                    ->orderBy('hour')
                    ->pluck('count', 'hour')
                    ->toArray(),
            ];
        });
    }

    /**
     * Cache staff performance metrics
     */
    public function getStaffPerformance($companyId, $staffId = null, $days = 30)
    {
        $cacheKey = "staff_performance:{$companyId}:" . ($staffId ?? 'all') . ":{$days}";
        
        return Cache::remember($cacheKey, $this->cacheTTL['aggregations'], function () use ($companyId, $staffId, $days) {
            $query = DB::table('staff')
                ->leftJoin('appointments', function ($join) use ($days) {
                    $join->on('staff.id', '=', 'appointments.staff_id')
                         ->where('appointments.starts_at', '>=', now()->subDays($days));
                })
                ->where('staff.company_id', $companyId)
                ->where('staff.active', true);
                
            if ($staffId) {
                $query->where('staff.id', $staffId);
            }
            
            return $query->groupBy('staff.id', 'staff.name')
                ->selectRaw('
                    staff.id,
                    staff.name,
                    COUNT(DISTINCT appointments.id) as total_appointments,
                    COUNT(DISTINCT CASE WHEN appointments.status = "completed" THEN appointments.id END) as completed_appointments,
                    COUNT(DISTINCT CASE WHEN appointments.status = "cancelled" THEN appointments.id END) as cancelled_appointments,
                    COUNT(DISTINCT CASE WHEN appointments.status = "no_show" THEN appointments.id END) as no_show_appointments,
                    COALESCE(SUM(CASE WHEN appointments.status = "completed" THEN appointments.price END), 0) as total_revenue,
                    COALESCE(AVG(CASE WHEN appointments.status = "completed" THEN appointments.price END), 0) as avg_revenue_per_appointment
                ')
                ->orderByDesc('total_revenue')
                ->get();
        });
    }

    /**
     * Cache branch comparison data
     */
    public function getBranchComparison($companyId, $dateRange = 'month')
    {
        $cacheKey = "branch_comparison:{$companyId}:{$dateRange}";
        
        return Cache::remember($cacheKey, $this->cacheTTL['reports'], function () use ($companyId, $dateRange) {
            $startDate = $this->getStartDate($dateRange);
            
            return DB::table('branches')
                ->leftJoin('appointments', function ($join) use ($startDate) {
                    $join->on('branches.id', '=', 'appointments.branch_id')
                         ->where('appointments.starts_at', '>=', $startDate);
                })
                ->leftJoin('staff_branches', 'branches.id', '=', 'staff_branches.branch_id')
                ->where('branches.company_id', $companyId)
                ->groupBy('branches.id', 'branches.name')
                ->selectRaw('
                    branches.id,
                    branches.name,
                    COUNT(DISTINCT appointments.id) as total_appointments,
                    COUNT(DISTINCT appointments.customer_id) as unique_customers,
                    COUNT(DISTINCT staff_branches.staff_id) as staff_count,
                    COALESCE(SUM(CASE WHEN appointments.status = "completed" THEN appointments.price END), 0) as revenue,
                    COALESCE(AVG(CASE WHEN appointments.status = "completed" THEN appointments.price END), 0) as avg_appointment_value
                ')
                ->orderByDesc('revenue')
                ->get();
        });
    }

    /**
     * Clear cache for a specific company
     */
    public function clearCompanyCache($companyId)
    {
        $patterns = [
            "appointment_stats:{$companyId}:*",
            "customer_metrics:{$companyId}",
            "call_stats:{$companyId}:*",
            "staff_performance:{$companyId}:*",
            "branch_comparison:{$companyId}:*",
        ];
        
        foreach ($patterns as $pattern) {
            $keys = Cache::getRedis()->keys($pattern);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }
        
        Log::info("Cleared query cache for company {$companyId}");
    }

    /**
     * Clear all query caches
     */
    public function clearAllCaches()
    {
        $patterns = [
            'appointment_stats:*',
            'customer_metrics:*',
            'call_stats:*',
            'staff_performance:*',
            'branch_comparison:*',
        ];
        
        foreach ($patterns as $pattern) {
            $keys = Cache::getRedis()->keys($pattern);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }
        
        Log::info('Cleared all query caches');
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats()
    {
        $stats = [];
        $patterns = [
            'appointment_stats' => 'appointment_stats:*',
            'customer_metrics' => 'customer_metrics:*',
            'call_stats' => 'call_stats:*',
            'staff_performance' => 'staff_performance:*',
            'branch_comparison' => 'branch_comparison:*',
        ];
        
        foreach ($patterns as $type => $pattern) {
            $keys = Cache::getRedis()->keys($pattern);
            $stats[$type] = [
                'count' => count($keys),
                'memory' => $this->calculateMemoryUsage($keys),
            ];
        }
        
        return $stats;
    }

    /**
     * Helper: Get start date based on range
     */
    private function getStartDate($range)
    {
        switch ($range) {
            case 'day':
                return now()->startOfDay();
            case 'week':
                return now()->startOfWeek();
            case 'month':
                return now()->startOfMonth();
            case 'quarter':
                return now()->startOfQuarter();
            case 'year':
                return now()->startOfYear();
            default:
                return now()->subDays(30);
        }
    }

    /**
     * Helper: Get daily distribution
     */
    private function getDailyDistribution($companyId, $startDate)
    {
        return DB::table('appointments')
            ->where('company_id', $companyId)
            ->where('starts_at', '>=', $startDate)
            ->selectRaw('DATE(starts_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }

    /**
     * Helper: Get hourly distribution
     */
    private function getHourlyDistribution($companyId, $startDate)
    {
        return DB::table('appointments')
            ->where('company_id', $companyId)
            ->where('starts_at', '>=', $startDate)
            ->selectRaw('HOUR(starts_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }

    /**
     * Helper: Calculate retention rate
     */
    private function calculateRetentionRate($companyId)
    {
        $sixMonthsAgo = now()->subMonths(6);
        
        $oldCustomers = DB::table('customers')
            ->where('company_id', $companyId)
            ->where('created_at', '<', $sixMonthsAgo)
            ->pluck('id');
            
        if ($oldCustomers->isEmpty()) {
            return 0;
        }
        
        $activeOldCustomers = DB::table('appointments')
            ->whereIn('customer_id', $oldCustomers)
            ->where('starts_at', '>=', now()->subMonths(3))
            ->distinct('customer_id')
            ->count('customer_id');
            
        return round(($activeOldCustomers / $oldCustomers->count()) * 100, 2);
    }

    /**
     * Helper: Calculate average lifetime value
     */
    private function calculateAverageLifetimeValue($companyId)
    {
        return DB::table('customers')
            ->join('appointments', 'customers.id', '=', 'appointments.customer_id')
            ->where('customers.company_id', $companyId)
            ->where('appointments.status', 'completed')
            ->groupBy('customers.id')
            ->selectRaw('SUM(appointments.price) as lifetime_value')
            ->pluck('lifetime_value')
            ->avg();
    }

    /**
     * Helper: Calculate memory usage
     */
    private function calculateMemoryUsage($keys)
    {
        $totalSize = 0;
        
        foreach ($keys as $key) {
            // This is an approximation
            $value = Cache::get($key);
            if ($value) {
                $totalSize += strlen(serialize($value));
            }
        }
        
        return round($totalSize / 1024, 2); // Return in KB
    }
}