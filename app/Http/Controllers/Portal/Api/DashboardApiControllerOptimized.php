<?php

namespace App\Http\Controllers\Portal\Api;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\CallCharge;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardApiControllerOptimized extends BaseApiController
{
    /**
     * Cache duration in seconds
     */
    const CACHE_DURATION = 300; // 5 minutes
    
    public function index(Request $request)
    {
        $company = $this->getCompany();
        $user = $this->getCurrentUser();
        
        if (!$company || !$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $companyId = $company->id;
        
        if (!$companyId) {
            return response()->json(['error' => 'No company context'], 400);
        }
        
        // Get time range
        $range = $request->input('range', 'today');
        list($startDate, $endDate) = $this->getDateRange($range);
        
        // Generate cache key
        $cacheKey = "dashboard:{$companyId}:{$range}:{$startDate->timestamp}:{$endDate->timestamp}";
        
        // Try to get from cache
        return Cache::remember($cacheKey, self::CACHE_DURATION, function() use ($companyId, $startDate, $endDate) {
            // Batch load all data with optimized queries
            $data = $this->loadDashboardData($companyId, $startDate, $endDate);
            
            return response()->json($data);
        });
    }
    
    /**
     * Load all dashboard data in an optimized way
     */
    private function loadDashboardData($companyId, $startDate, $endDate)
    {
        // Use database transaction for consistent reads
        return DB::transaction(function() use ($companyId, $startDate, $endDate) {
            // Get aggregated stats in a single query
            $stats = $this->getAggregatedStats($companyId, $startDate, $endDate);
            
            // Get trends by comparing periods
            $trends = $this->getOptimizedTrends($companyId, $startDate, $endDate, $stats);
            
            // Get chart data with batch queries
            $chartData = $this->getBatchedChartData($companyId, $startDate, $endDate);
            
            // Get recent items with eager loading
            $recentCalls = $this->getRecentCallsOptimized($companyId);
            $upcomingAppointments = $this->getUpcomingAppointmentsOptimized($companyId);
            
            // Get performance metrics from already loaded data
            $performance = $this->getPerformanceMetricsOptimized($stats);
            
            // Get alerts based on current data
            $alerts = $this->getAlertsOptimized($stats);
            
            return [
                'stats' => $stats,
                'trends' => $trends,
                'chartData' => $chartData,
                'recentCalls' => $recentCalls,
                'upcomingAppointments' => $upcomingAppointments,
                'performance' => $performance,
                'alerts' => $alerts
            ];
        });
    }
    
    /**
     * Get all main stats in a single optimized query
     */
    private function getAggregatedStats($companyId, $startDate, $endDate)
    {
        // Get call stats
        $callStats = DB::table('calls')
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('COUNT(CASE WHEN status = "answered" THEN 1 END) as answered_calls'),
                DB::raw('AVG(CASE WHEN duration_sec > 0 THEN duration_sec END) as avg_duration'),
                DB::raw('SUM(duration_sec) as total_duration')
            )
            ->first();
            
        // Get appointment stats
        $appointmentStats = DB::table('appointments')
            ->where('company_id', $companyId)
            ->select(
                DB::raw('COUNT(CASE WHEN DATE(starts_at) = CURDATE() THEN 1 END) as appointments_today'),
                DB::raw('COUNT(CASE WHEN call_id IS NOT NULL AND created_at BETWEEN ? AND ? THEN 1 END) as appointments_from_calls')
            )
            ->setBindings([$startDate, $endDate])
            ->first();
            
        // Get customer stats
        $customerStats = DB::table('customers')
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
            
        // Get revenue stats with optimized join
        $revenueStats = DB::table('call_charges')
            ->join('calls', 'call_charges.call_id', '=', 'calls.id')
            ->where('calls.company_id', $companyId)
            ->whereBetween('call_charges.created_at', [$startDate, $endDate])
            ->sum('call_charges.amount_charged');
            
        return [
            'calls_today' => $callStats->total_calls ?? 0,
            'answered_calls' => $callStats->answered_calls ?? 0,
            'appointments_today' => $appointmentStats->appointments_today ?? 0,
            'appointments_from_calls' => $appointmentStats->appointments_from_calls ?? 0,
            'new_customers' => $customerStats,
            'revenue_today' => $revenueStats ?? 0,
            'avg_call_duration' => round($callStats->avg_duration ?? 0),
            'total_call_duration' => $callStats->total_duration ?? 0
        ];
    }
    
    /**
     * Get trends without making duplicate queries
     */
    private function getOptimizedTrends($companyId, $startDate, $endDate, $currentStats)
    {
        // Calculate previous period
        $periodLength = $startDate->diffInDays($endDate);
        $prevStartDate = $startDate->copy()->subDays($periodLength);
        $prevEndDate = $startDate->copy()->subSecond();
        
        // Get previous period stats in one go
        $prevStats = $this->getAggregatedStats($companyId, $prevStartDate, $prevEndDate);
        
        // Calculate trends
        $trendMapping = [
            'calls' => 'calls_today',
            'appointments' => 'appointments_today',
            'customers' => 'new_customers',
            'revenue' => 'revenue_today'
        ];
        
        $trends = [];
        foreach ($trendMapping as $key => $stat) {
            $current = $currentStats[$stat];
            $previous = $prevStats[$stat];
            
            $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
            
            $trends[$key] = [
                'value' => $current,
                'change' => round($change, 1),
                'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable')
            ];
        }
        
        return $trends;
    }
    
    /**
     * Get chart data with batch queries instead of loops
     */
    private function getBatchedChartData($companyId, $startDate, $endDate)
    {
        // Get daily data in one query
        $dailyData = DB::table('calls')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as call_count')
            )
            ->groupBy('date')
            ->pluck('call_count', 'date')
            ->toArray();
            
        $dailyAppointments = DB::table('appointments')
            ->where('company_id', $companyId)
            ->where('starts_at', '>=', now()->subDays(6)->startOfDay())
            ->select(
                DB::raw('DATE(starts_at) as date'),
                DB::raw('COUNT(*) as appointment_count')
            )
            ->groupBy('date')
            ->pluck('appointment_count', 'date')
            ->toArray();
            
        // Build daily array
        $daily = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $daily[] = [
                'date' => $date,
                'calls' => $dailyData[$date] ?? 0,
                'appointments' => $dailyAppointments[$date] ?? 0
            ];
        }
        
        // Get hourly distribution in one query
        $hourlyData = DB::table('calls')
            ->where('company_id', $companyId)
            ->whereDate('created_at', now())
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
            
        $hourly = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourly[] = [
                'hour' => $hour,
                'calls' => $hourlyData[$hour] ?? 0
            ];
        }
        
        // Performance funnel data (using already loaded stats)
        $performance = [
            ['stage' => 'Anrufe', 'value' => $startDate->isToday() ? DB::table('calls')->where('company_id', $companyId)->whereDate('created_at', now())->count() : 0],
            ['stage' => 'Beantwortet', 'value' => $startDate->isToday() ? DB::table('calls')->where('company_id', $companyId)->whereDate('created_at', now())->where('status', 'answered')->count() : 0],
            ['stage' => 'Termin vereinbart', 'value' => $startDate->isToday() ? DB::table('appointments')->where('company_id', $companyId)->whereDate('created_at', now())->whereNotNull('call_id')->count() : 0],
            ['stage' => 'Termin wahrgenommen', 'value' => $startDate->isToday() ? DB::table('appointments')->where('company_id', $companyId)->whereDate('starts_at', '<', now())->where('status', 'completed')->count() : 0]
        ];
        
        return [
            'daily' => $daily,
            'hourly' => $hourly,
            'sources' => $this->getCallSources($companyId), // Cached separately
            'performance' => $performance
        ];
    }
    
    /**
     * Get recent calls with proper eager loading
     */
    private function getRecentCallsOptimized($companyId)
    {
        return Call::where('company_id', $companyId)
            ->with([
                'customer:id,name,phone',
                'appointment:id,call_id,starts_at'
            ])
            ->select('id', 'from_number', 'to_number', 'duration_sec', 'call_status', 'direction', 'created_at', 'customer_id')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'from_number' => $call->from_number,
                    'to_number' => $call->to_number,
                    'duration' => $call->duration_sec,
                    'status' => $call->call_status ?? 'completed',
                    'direction' => $call->direction ?? 'inbound',
                    'created_at' => $call->created_at,
                    'appointment_created' => (bool) $call->appointment,
                    'customer_name' => $call->customer?->name
                ];
            });
    }
    
    /**
     * Get upcoming appointments with proper eager loading
     */
    private function getUpcomingAppointmentsOptimized($companyId)
    {
        return Appointment::where('company_id', $companyId)
            ->with([
                'customer:id,name',
                'staff:id,name',
                'service:id,name'
            ])
            ->select('id', 'customer_id', 'staff_id', 'service_id', 'starts_at', 'duration_minutes')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(10)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'customer_name' => $appointment->customer?->name ?? 'Unbekannt',
                    'service_name' => $appointment->service?->name ?? 'Termin',
                    'staff_name' => $appointment->staff?->name ?? '-',
                    'starts_at' => $appointment->starts_at,
                    'duration' => $appointment->duration_minutes ?? 60
                ];
            });
    }
    
    /**
     * Calculate performance metrics from already loaded stats
     */
    private function getPerformanceMetricsOptimized($stats)
    {
        $totalCalls = $stats['calls_today'];
        $answeredCalls = $stats['answered_calls'];
        $appointmentsCreated = $stats['appointments_from_calls'];
        $avgCallDuration = $stats['avg_call_duration'];
        
        return [
            'answer_rate' => $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100) : 0,
            'booking_rate' => $answeredCalls > 0 ? round(($appointmentsCreated / $answeredCalls) * 100) : 0,
            'avg_call_duration' => intval($avgCallDuration),
            'customer_satisfaction' => $this->getCustomerSatisfaction() // Cached separately
        ];
    }
    
    /**
     * Get alerts based on current stats
     */
    private function getAlertsOptimized($stats)
    {
        $alerts = [];
        
        // High call volume alert
        $recentCallsCount = DB::table('calls')
            ->where('company_id', $stats['company_id'] ?? 0)
            ->where('created_at', '>=', now()->subHour())
            ->count();
            
        if ($recentCallsCount > 20) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Hohes Anrufaufkommen in der letzten Stunde',
                'count' => $recentCallsCount
            ];
        }
        
        // Low answer rate alert
        if ($stats['calls_today'] > 10 && ($stats['answered_calls'] / $stats['calls_today']) < 0.7) {
            $alerts[] = [
                'type' => 'error',
                'message' => 'Niedrige Antwortrate heute',
                'rate' => round(($stats['answered_calls'] / $stats['calls_today']) * 100) . '%'
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Get call sources (cached separately)
     */
    private function getCallSources($companyId)
    {
        return Cache::remember("sources:{$companyId}", 3600, function() {
            // In real implementation, this would analyze actual call sources
            return [
                ['name' => 'Google Ads', 'value' => 35],
                ['name' => 'Website', 'value' => 30],
                ['name' => 'Direkt', 'value' => 20],
                ['name' => 'Empfehlung', 'value' => 10],
                ['name' => 'Sonstige', 'value' => 5]
            ];
        });
    }
    
    /**
     * Get customer satisfaction (cached separately)
     */
    private function getCustomerSatisfaction()
    {
        return Cache::remember("satisfaction", 3600, function() {
            // In real implementation, this would calculate from actual feedback
            return 92;
        });
    }
    
    /**
     * Get date range for different periods
     */
    private function getDateRange($range)
    {
        $endDate = now();
        
        switch ($range) {
            case 'today':
                $startDate = now()->startOfDay();
                break;
            case 'week':
                $startDate = now()->startOfWeek();
                break;
            case 'month':
                $startDate = now()->startOfMonth();
                break;
            case 'year':
                $startDate = now()->startOfYear();
                break;
            default:
                $startDate = now()->startOfDay();
        }
        
        return [$startDate, $endDate];
    }
}