<?php

namespace App\Services\Dashboard;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\CallCharge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class OptimizedDashboardService
{
    /**
     * Cache duration constants
     */
    const CACHE_SHORT = 300; // 5 minutes
    const CACHE_MEDIUM = 1800; // 30 minutes
    const CACHE_LONG = 3600; // 1 hour
    
    /**
     * Get complete dashboard data with optimized queries
     */
    public function getDashboardData(int $companyId, string $range = 'today'): array
    {
        list($startDate, $endDate) = $this->getDateRange($range);
        
        // Generate cache key
        $cacheKey = "dashboard:v2:{$companyId}:{$range}:{$startDate->timestamp}";
        
        // Try to get from cache
        return Cache::remember($cacheKey, self::CACHE_SHORT, function() use ($companyId, $startDate, $endDate) {
            return $this->loadDashboardData($companyId, $startDate, $endDate);
        });
    }
    
    /**
     * Load all dashboard data efficiently
     */
    private function loadDashboardData(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        // Use database transaction for consistent reads
        return DB::transaction(function() use ($companyId, $startDate, $endDate) {
            // Preload all necessary data with optimized queries
            $stats = $this->loadStats($companyId, $startDate, $endDate);
            $trends = $this->calculateTrends($companyId, $startDate, $endDate, $stats);
            $chartData = $this->loadChartData($companyId, $startDate, $endDate);
            $recentActivity = $this->loadRecentActivity($companyId);
            $performance = $this->calculatePerformance($stats);
            $alerts = $this->generateAlerts($companyId, $stats);
            
            return [
                'stats' => $stats,
                'trends' => $trends,
                'charts' => $chartData,
                'activity' => $recentActivity,
                'performance' => $performance,
                'alerts' => $alerts,
                'generated_at' => now()->toIso8601String()
            ];
        });
    }
    
    /**
     * Load main statistics with single query per table
     */
    private function loadStats(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        // Get all call statistics in one query
        $callStats = DB::table('calls')
            ->where('company_id', $companyId)
            ->selectRaw('
                COUNT(*) as total_calls,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as period_calls,
                COUNT(CASE WHEN status = "answered" AND created_at BETWEEN ? AND ? THEN 1 END) as answered_calls,
                AVG(CASE WHEN duration_sec > 0 THEN duration_sec END) as avg_duration,
                MAX(created_at) as last_call_at
            ', [$startDate, $endDate, $startDate, $endDate])
            ->first();
            
        // Get appointment statistics
        $appointmentStats = DB::table('appointments')
            ->where('company_id', $companyId)
            ->selectRaw('
                COUNT(CASE WHEN starts_at BETWEEN ? AND ? THEN 1 END) as period_appointments,
                COUNT(CASE WHEN DATE(starts_at) = CURDATE() THEN 1 END) as today_appointments,
                COUNT(CASE WHEN starts_at > NOW() THEN 1 END) as upcoming_appointments,
                COUNT(CASE WHEN call_id IS NOT NULL AND created_at BETWEEN ? AND ? THEN 1 END) as appointments_from_calls
            ', [$startDate, $endDate, $startDate, $endDate])
            ->first();
            
        // Get customer statistics
        $customerStats = DB::table('customers')
            ->where('company_id', $companyId)
            ->selectRaw('
                COUNT(*) as total_customers,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_customers
            ', [$startDate, $endDate])
            ->first();
            
        // Get revenue statistics with join
        $revenueStats = DB::table('call_charges as cc')
            ->join('calls as c', 'cc.call_id', '=', 'c.id')
            ->where('c.company_id', $companyId)
            ->whereBetween('cc.created_at', [$startDate, $endDate])
            ->selectRaw('
                SUM(cc.amount_charged) as total_revenue,
                AVG(cc.amount_charged) as avg_revenue_per_call,
                COUNT(DISTINCT cc.call_id) as charged_calls
            ')
            ->first();
            
        return [
            // Call metrics
            'total_calls' => $callStats->total_calls ?? 0,
            'period_calls' => $callStats->period_calls ?? 0,
            'answered_calls' => $callStats->answered_calls ?? 0,
            'avg_call_duration' => round($callStats->avg_duration ?? 0),
            'last_call_at' => $callStats->last_call_at,
            
            // Appointment metrics
            'period_appointments' => $appointmentStats->period_appointments ?? 0,
            'today_appointments' => $appointmentStats->today_appointments ?? 0,
            'upcoming_appointments' => $appointmentStats->upcoming_appointments ?? 0,
            'appointments_from_calls' => $appointmentStats->appointments_from_calls ?? 0,
            
            // Customer metrics
            'total_customers' => $customerStats->total_customers ?? 0,
            'new_customers' => $customerStats->new_customers ?? 0,
            
            // Revenue metrics
            'total_revenue' => $revenueStats->total_revenue ?? 0,
            'avg_revenue_per_call' => round($revenueStats->avg_revenue_per_call ?? 0, 2),
            'charged_calls' => $revenueStats->charged_calls ?? 0
        ];
    }
    
    /**
     * Calculate trends by comparing periods
     */
    private function calculateTrends(int $companyId, Carbon $startDate, Carbon $endDate, array $currentStats): array
    {
        // Calculate previous period
        $periodLength = $startDate->diffInDays($endDate) ?: 1;
        $prevStartDate = $startDate->copy()->subDays($periodLength);
        $prevEndDate = $startDate->copy()->subSecond();
        
        // Get previous period stats (cached separately)
        $cacheKey = "dashboard:prev:{$companyId}:{$prevStartDate->timestamp}:{$prevEndDate->timestamp}";
        $prevStats = Cache::remember($cacheKey, self::CACHE_MEDIUM, function() use ($companyId, $prevStartDate, $prevEndDate) {
            return $this->loadStats($companyId, $prevStartDate, $prevEndDate);
        });
        
        // Calculate percentage changes
        $trends = [];
        $metrics = [
            'calls' => 'period_calls',
            'appointments' => 'period_appointments',
            'customers' => 'new_customers',
            'revenue' => 'total_revenue'
        ];
        
        foreach ($metrics as $key => $metric) {
            $current = $currentStats[$metric] ?? 0;
            $previous = $prevStats[$metric] ?? 0;
            
            $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
            
            $trends[$key] = [
                'current' => $current,
                'previous' => $previous,
                'change' => round($change, 1),
                'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
                'improved' => $change > 0
            ];
        }
        
        return $trends;
    }
    
    /**
     * Load chart data with batch queries
     */
    private function loadChartData(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        // Cache chart data for longer as it changes less frequently
        $cacheKey = "dashboard:charts:{$companyId}:{$startDate->toDateString()}";
        
        return Cache::remember($cacheKey, self::CACHE_MEDIUM, function() use ($companyId) {
            return [
                'daily' => $this->getDailyChartData($companyId),
                'hourly' => $this->getHourlyChartData($companyId),
                'sources' => $this->getCallSourcesData($companyId),
                'funnel' => $this->getConversionFunnel($companyId)
            ];
        });
    }
    
    /**
     * Get daily activity for the last 7 days
     */
    private function getDailyChartData(int $companyId): array
    {
        $startDate = now()->subDays(6)->startOfDay();
        
        // Get call data
        $callData = DB::table('calls')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->pluck('count', 'date')
            ->toArray();
            
        // Get appointment data
        $appointmentData = DB::table('appointments')
            ->where('company_id', $companyId)
            ->where('starts_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->selectRaw('DATE(starts_at) as date, COUNT(*) as count')
            ->pluck('count', 'date')
            ->toArray();
            
        // Build result array
        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $result[] = [
                'date' => $date,
                'day' => now()->subDays($i)->format('D'),
                'calls' => $callData[$date] ?? 0,
                'appointments' => $appointmentData[$date] ?? 0
            ];
        }
        
        return $result;
    }
    
    /**
     * Get hourly distribution for today
     */
    private function getHourlyChartData(int $companyId): array
    {
        $data = DB::table('calls')
            ->where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->groupBy('hour')
            ->orderBy('hour')
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->pluck('count', 'hour')
            ->toArray();
            
        $result = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $result[] = [
                'hour' => sprintf('%02d:00', $hour),
                'calls' => $data[$hour] ?? 0
            ];
        }
        
        return $result;
    }
    
    /**
     * Get call sources distribution
     */
    private function getCallSourcesData(int $companyId): array
    {
        // In a real implementation, this would analyze actual call sources
        // For now, return mock data that changes daily
        $seed = $companyId + now()->dayOfYear;
        srand($seed);
        
        $sources = [
            'Google Ads' => rand(25, 40),
            'Website' => rand(20, 35),
            'Direct' => rand(15, 25),
            'Referral' => rand(10, 20),
            'Other' => rand(5, 15)
        ];
        
        // Normalize to 100%
        $total = array_sum($sources);
        $result = [];
        
        foreach ($sources as $name => $value) {
            $result[] = [
                'name' => $name,
                'value' => round(($value / $total) * 100),
                'count' => $value
            ];
        }
        
        return $result;
    }
    
    /**
     * Get conversion funnel data
     */
    private function getConversionFunnel(int $companyId): array
    {
        $today = now()->startOfDay();
        
        $data = DB::table('calls as c')
            ->where('c.company_id', $companyId)
            ->whereDate('c.created_at', $today)
            ->selectRaw('
                COUNT(*) as total_calls,
                COUNT(CASE WHEN c.status = "answered" THEN 1 END) as answered_calls,
                COUNT(DISTINCT a.id) as appointments_created,
                COUNT(CASE WHEN a.status = "completed" THEN 1 END) as appointments_completed
            ')
            ->leftJoin('appointments as a', function($join) {
                $join->on('a.call_id', '=', 'c.id')
                     ->whereNotNull('a.call_id');
            })
            ->first();
            
        return [
            ['stage' => 'Total Calls', 'value' => $data->total_calls ?? 0, 'rate' => 100],
            ['stage' => 'Answered', 'value' => $data->answered_calls ?? 0, 'rate' => $data->total_calls > 0 ? round(($data->answered_calls / $data->total_calls) * 100) : 0],
            ['stage' => 'Appointment Created', 'value' => $data->appointments_created ?? 0, 'rate' => $data->answered_calls > 0 ? round(($data->appointments_created / $data->answered_calls) * 100) : 0],
            ['stage' => 'Appointment Completed', 'value' => $data->appointments_completed ?? 0, 'rate' => $data->appointments_created > 0 ? round(($data->appointments_completed / $data->appointments_created) * 100) : 0]
        ];
    }
    
    /**
     * Load recent activity with eager loading
     */
    private function loadRecentActivity(int $companyId): array
    {
        // Recent calls with relationships
        $recentCalls = Call::where('company_id', $companyId)
            ->with([
                'customer:id,name,phone',
                'appointment:id,call_id,starts_at,status'
            ])
            ->select('id', 'from_number', 'to_number', 'duration_sec', 'status', 'direction', 'created_at', 'customer_id')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        // Upcoming appointments with relationships
        $upcomingAppointments = Appointment::where('company_id', $companyId)
            ->with([
                'customer:id,name',
                'staff:id,name',
                'service:id,name'
            ])
            ->select('id', 'customer_id', 'staff_id', 'service_id', 'starts_at', 'duration_minutes', 'status')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(10)
            ->get();
            
        return [
            'recent_calls' => $recentCalls->map(function($call) {
                return [
                    'id' => $call->id,
                    'from' => $call->from_number,
                    'to' => $call->to_number,
                    'duration' => $call->duration_sec,
                    'status' => $call->status,
                    'direction' => $call->direction,
                    'created_at' => $call->created_at->toIso8601String(),
                    'customer_name' => $call->customer?->name,
                    'has_appointment' => (bool) $call->appointment,
                    'appointment_status' => $call->appointment?->status
                ];
            }),
            'upcoming_appointments' => $upcomingAppointments->map(function($appointment) {
                return [
                    'id' => $appointment->id,
                    'customer_name' => $appointment->customer?->name ?? 'Unknown',
                    'staff_name' => $appointment->staff?->name ?? 'Unassigned',
                    'service_name' => $appointment->service?->name ?? 'General',
                    'starts_at' => $appointment->starts_at->toIso8601String(),
                    'duration' => $appointment->duration_minutes,
                    'status' => $appointment->status
                ];
            })
        ];
    }
    
    /**
     * Calculate performance metrics from stats
     */
    private function calculatePerformance(array $stats): array
    {
        return [
            'answer_rate' => $stats['period_calls'] > 0 
                ? round(($stats['answered_calls'] / $stats['period_calls']) * 100) 
                : 0,
            'booking_rate' => $stats['answered_calls'] > 0 
                ? round(($stats['appointments_from_calls'] / $stats['answered_calls']) * 100) 
                : 0,
            'avg_call_duration' => $stats['avg_call_duration'],
            'revenue_per_call' => $stats['period_calls'] > 0 
                ? round($stats['total_revenue'] / $stats['period_calls'], 2) 
                : 0,
            'customer_acquisition_rate' => $stats['period_calls'] > 0 
                ? round(($stats['new_customers'] / $stats['period_calls']) * 100) 
                : 0
        ];
    }
    
    /**
     * Generate alerts based on current metrics
     */
    private function generateAlerts(int $companyId, array $stats): array
    {
        $alerts = [];
        
        // Check for high call volume
        if ($stats['period_calls'] > 100) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'High Call Volume',
                'message' => 'You\'ve received ' . $stats['period_calls'] . ' calls today',
                'icon' => 'phone'
            ];
        }
        
        // Check for low answer rate
        if ($stats['period_calls'] > 10 && $stats['answered_calls'] / $stats['period_calls'] < 0.7) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Low Answer Rate',
                'message' => 'Only ' . round(($stats['answered_calls'] / $stats['period_calls']) * 100) . '% of calls were answered',
                'icon' => 'alert-triangle'
            ];
        }
        
        // Check for appointments today
        if ($stats['today_appointments'] > 0 && $stats['today_appointments'] < 5) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Appointments Today',
                'message' => 'You have ' . $stats['today_appointments'] . ' appointments scheduled for today',
                'icon' => 'calendar'
            ];
        }
        
        // Check for no recent activity
        if ($stats['last_call_at'] && Carbon::parse($stats['last_call_at'])->diffInHours(now()) > 2) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'No Recent Activity',
                'message' => 'No calls received in the last 2 hours',
                'icon' => 'clock'
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Get date range based on period string
     */
    private function getDateRange(string $range): array
    {
        $endDate = now();
        
        switch ($range) {
            case 'today':
                $startDate = now()->startOfDay();
                break;
            case 'yesterday':
                $startDate = now()->subDay()->startOfDay();
                $endDate = now()->subDay()->endOfDay();
                break;
            case 'week':
                $startDate = now()->startOfWeek();
                break;
            case 'last_week':
                $startDate = now()->subWeek()->startOfWeek();
                $endDate = now()->subWeek()->endOfWeek();
                break;
            case 'month':
                $startDate = now()->startOfMonth();
                break;
            case 'last_month':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                break;
            case 'year':
                $startDate = now()->startOfYear();
                break;
            default:
                $startDate = now()->startOfDay();
        }
        
        return [$startDate, $endDate];
    }
    
    /**
     * Clear dashboard cache for a company
     */
    public function clearCache(int $companyId): void
    {
        $patterns = [
            "dashboard:*:{$companyId}:*",
            "dashboard:charts:{$companyId}:*",
            "dashboard:prev:{$companyId}:*"
        ];
        
        foreach ($patterns as $pattern) {
            $keys = Cache::getRedis()->keys($pattern);
            if (!empty($keys)) {
                Cache::deleteMultiple($keys);
            }
        }
    }
}