<?php

namespace App\Services\Analytics;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RoiCalculationService
{
    private const CACHE_TTL = 300; // 5 minutes
    
    /**
     * Calculate ROI for a specific period and scope
     */
    public function calculateRoi(
        Company $company,
        Carbon $startDate,
        Carbon $endDate,
        ?Branch $branch = null,
        array $options = []
    ): array {
        $cacheKey = $this->getCacheKey($company, $startDate, $endDate, $branch, $options);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($company, $startDate, $endDate, $branch, $options) {
            // Get base metrics
            $callMetrics = $this->getCallMetrics($company, $startDate, $endDate, $branch);
            $appointmentMetrics = $this->getAppointmentMetrics($company, $startDate, $endDate, $branch);
            $hourlyBreakdown = $this->getHourlyBreakdown($company, $startDate, $endDate, $branch);
            
            // Calculate ROI
            $totalRevenue = $appointmentMetrics['total_revenue'] ?? 0;
            $totalCosts = $callMetrics['total_cost'] ?? 0;
            $roi = $totalCosts > 0 ? (($totalRevenue - $totalCosts) / $totalCosts) * 100 : 0;
            
            // Business hours analysis
            $businessHoursAnalysis = $this->analyzeBusinessHours($hourlyBreakdown, $branch);
            
            return [
                'summary' => [
                    'roi_percentage' => round($roi, 2),
                    'total_revenue' => $totalRevenue,
                    'total_costs' => $totalCosts,
                    'net_profit' => $totalRevenue - $totalCosts,
                    'profit_margin' => $totalRevenue > 0 ? round((($totalRevenue - $totalCosts) / $totalRevenue) * 100, 2) : 0,
                    'cost_per_euro_revenue' => $totalRevenue > 0 ? round($totalCosts / $totalRevenue, 2) : 0,
                ],
                'call_metrics' => $callMetrics,
                'appointment_metrics' => $appointmentMetrics,
                'hourly_breakdown' => $hourlyBreakdown,
                'business_hours_analysis' => $businessHoursAnalysis,
                'period' => [
                    'start' => $startDate->format('d.m.Y'),
                    'end' => $endDate->format('d.m.Y'),
                    'days' => $startDate->diffInDays($endDate) + 1,
                ],
            ];
        });
    }
    
    /**
     * Get aggregated ROI for all branches - optimized for memory efficiency
     */
    public function getCompanyWideRoi(Company $company, Carbon $startDate, Carbon $endDate): array
    {
        // Get company total first
        $companyTotal = $this->calculateRoi($company, $startDate, $endDate);
        
        // Process branches in chunks to avoid memory issues
        $branchBreakdown = [];
        $company->branches()->chunk(10, function ($branches) use ($company, $startDate, $endDate, &$branchBreakdown) {
            foreach ($branches as $branch) {
                $branchRoi = $this->calculateRoi($company, $startDate, $endDate, $branch);
                $branchBreakdown[] = [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'roi_percentage' => $branchRoi['summary']['roi_percentage'],
                    'revenue' => $branchRoi['summary']['total_revenue'],
                    'costs' => $branchRoi['summary']['total_costs'],
                    'profit' => $branchRoi['summary']['net_profit'],
                    'calls' => $branchRoi['call_metrics']['total_calls'] ?? 0,
                    'bookings' => $branchRoi['appointment_metrics']['total_appointments'] ?? 0,
                    'conversion_rate' => $branchRoi['call_metrics']['total_calls'] > 0 
                        ? round(($branchRoi['call_metrics']['calls_with_bookings'] / $branchRoi['call_metrics']['total_calls']) * 100, 2)
                        : 0,
                ];
            }
        });
        
        // Sort branches by ROI
        usort($branchBreakdown, fn($a, $b) => $b['roi_percentage'] <=> $a['roi_percentage']);
        
        return [
            'company_total' => $companyTotal,
            'branch_breakdown' => $branchBreakdown,
            'top_performer' => $branchBreakdown[0] ?? null,
            'bottom_performer' => end($branchBreakdown) ?: null,
        ];
    }
    
    /**
     * Get call metrics using database aggregation
     */
    private function getCallMetrics(Company $company, Carbon $startDate, Carbon $endDate, ?Branch $branch): array
    {
        $result = DB::table('calls')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->when($branch, fn($q) => $q->where('branch_id', $branch->id))
            ->select(
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('COALESCE(SUM(duration_sec * 0.02), 0) as total_cost'),
                DB::raw('COALESCE(AVG(duration_sec * 0.02), 0) as avg_cost_per_call'),
                DB::raw('SUM(CASE WHEN metadata IS NOT NULL AND metadata LIKE \'%appointment%\' THEN 1 ELSE 0 END) as calls_with_bookings'),
                DB::raw('COALESCE(AVG(duration_sec), 0) as avg_duration_seconds'),
                DB::raw('COALESCE(SUM(duration_sec), 0) as total_duration_seconds')
            )
            ->first();
            
        // Convert stdClass to array
        return $result ? (array) $result : [
            'total_calls' => 0,
            'total_cost' => 0,
            'avg_cost_per_call' => 0,
            'calls_with_bookings' => 0,
            'avg_duration_seconds' => 0,
            'total_duration_seconds' => 0
        ];
    }
    
    /**
     * Get appointment metrics using database aggregation
     */
    private function getAppointmentMetrics(Company $company, Carbon $startDate, Carbon $endDate, ?Branch $branch): array
    {
        $query = DB::table('appointments')
            ->join('branches', 'appointments.branch_id', '=', 'branches.id')
            ->leftJoin('services', 'appointments.service_id', '=', 'services.id')
            ->where('branches.company_id', $company->id)
            ->whereBetween('appointments.starts_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereIn('appointments.status', ['completed', 'confirmed'])
            ->when($branch, fn($q) => $q->where('appointments.branch_id', $branch->id));
            
        $result = $query->select(
            DB::raw('COUNT(*) as total_appointments'),
            DB::raw('COALESCE(SUM(services.price), 0) as total_revenue'),
            DB::raw('COALESCE(AVG(services.price), 0) as avg_revenue_per_appointment'),
            DB::raw('COUNT(DISTINCT appointments.customer_id) as unique_customers')
        )->first();
        
        // Convert stdClass to array
        return $result ? (array) $result : [
            'total_appointments' => 0,
            'total_revenue' => 0,
            'avg_revenue_per_appointment' => 0,
            'unique_customers' => 0
        ];
    }
    
    /**
     * Calculate hourly breakdown for business hours analysis
     */
    private function getHourlyBreakdown(Company $company, Carbon $startDate, Carbon $endDate, ?Branch $branch): array
    {
        // Get call costs by hour
        $hourlyCalls = DB::table('calls')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->when($branch, fn($q) => $q->where('branch_id', $branch->id))
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as call_count'),
                DB::raw('COALESCE(SUM(duration_sec * 0.02), 0) as hour_cost'),
                DB::raw('SUM(CASE WHEN metadata IS NOT NULL AND metadata LIKE \'%appointment%\' THEN 1 ELSE 0 END) as bookings')
            )
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');
            
        // Get appointment revenue by hour
        $hourlyRevenue = DB::table('appointments')
            ->join('branches', 'appointments.branch_id', '=', 'branches.id')
            ->leftJoin('services', 'appointments.service_id', '=', 'services.id')
            ->where('branches.company_id', $company->id)
            ->whereBetween('appointments.starts_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereIn('appointments.status', ['completed', 'confirmed'])
            ->when($branch, fn($q) => $q->where('appointments.branch_id', $branch->id))
            ->select(
                DB::raw('HOUR(appointments.starts_at) as hour'),
                DB::raw('COALESCE(SUM(services.price), 0) as hour_revenue')
            )
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');
            
        // Build complete hourly breakdown
        $breakdown = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $callData = $hourlyCalls->get($hour);
            $revenueData = $hourlyRevenue->get($hour);
            
            $hourCost = $callData ? $callData->hour_cost : 0;
            $hourRevenue = $revenueData ? $revenueData->hour_revenue : 0;
            $hourRoi = $hourCost > 0 ? (($hourRevenue - $hourCost) / $hourCost) * 100 : 0;
            
            $breakdown[$hour] = [
                'hour' => $hour,
                'hour_label' => sprintf('%02d:00', $hour),
                'calls' => $callData ? $callData->call_count : 0,
                'cost' => $hourCost,
                'revenue' => $hourRevenue,
                'profit' => $hourRevenue - $hourCost,
                'roi' => round($hourRoi, 2),
                'bookings' => $callData ? $callData->bookings : 0,
                'conversion_rate' => ($callData && $callData->call_count > 0) 
                    ? round($callData->bookings / $callData->call_count * 100, 2) 
                    : 0,
            ];
        }
        
        return $breakdown;
    }
    
    /**
     * Analyze business hours vs after hours performance
     */
    private function analyzeBusinessHours(array $hourlyBreakdown, ?Branch $branch): array
    {
        // Define business hours (can be customized per branch)
        $businessHours = $this->getBusinessHoursRange($branch);
        
        $businessHoursMetrics = [
            'revenue' => 0,
            'costs' => 0,
            'calls' => 0,
            'bookings' => 0,
        ];
        
        $afterHoursMetrics = [
            'revenue' => 0,
            'costs' => 0,
            'calls' => 0,
            'bookings' => 0,
        ];
        
        foreach ($hourlyBreakdown as $hour => $data) {
            if ($hour >= $businessHours['start'] && $hour < $businessHours['end']) {
                $businessHoursMetrics['revenue'] += $data['revenue'];
                $businessHoursMetrics['costs'] += $data['cost'];
                $businessHoursMetrics['calls'] += $data['calls'];
                $businessHoursMetrics['bookings'] += $data['bookings'];
            } else {
                $afterHoursMetrics['revenue'] += $data['revenue'];
                $afterHoursMetrics['costs'] += $data['cost'];
                $afterHoursMetrics['calls'] += $data['calls'];
                $afterHoursMetrics['bookings'] += $data['bookings'];
            }
        }
        
        // Calculate ROI for each period
        $businessHoursMetrics['roi'] = $businessHoursMetrics['costs'] > 0 
            ? round((($businessHoursMetrics['revenue'] - $businessHoursMetrics['costs']) / $businessHoursMetrics['costs']) * 100, 2)
            : 0;
            
        $afterHoursMetrics['roi'] = $afterHoursMetrics['costs'] > 0 
            ? round((($afterHoursMetrics['revenue'] - $afterHoursMetrics['costs']) / $afterHoursMetrics['costs']) * 100, 2)
            : 0;
            
        $businessHoursMetrics['profit'] = $businessHoursMetrics['revenue'] - $businessHoursMetrics['costs'];
        $afterHoursMetrics['profit'] = $afterHoursMetrics['revenue'] - $afterHoursMetrics['costs'];
        
        return [
            'business_hours' => $businessHoursMetrics,
            'after_hours' => $afterHoursMetrics,
            'business_hours_range' => $businessHours,
        ];
    }
    
    /**
     * Get business hours configuration
     */
    private function getBusinessHoursRange(?Branch $branch): array
    {
        // Default business hours if not configured
        // Can be extended to read from branch settings
        return [
            'start' => 9,  // 9:00 AM
            'end' => 18,   // 6:00 PM
        ];
    }
    
    /**
     * Generate cache key
     */
    private function getCacheKey(Company $company, Carbon $startDate, Carbon $endDate, ?Branch $branch, array $options): string
    {
        $key = "roi:{$company->id}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        if ($branch) {
            $key .= ":{$branch->id}";
        }
        if (!empty($options)) {
            $key .= ':' . md5(json_encode($options));
        }
        return $key;
    }
    
    /**
     * Get ROI trend for a period - optimized to avoid memory issues
     */
    public function getRoiTrend(Company $company, int $days = 30, ?Branch $branch = null): array
    {
        $endDate = Carbon::today();
        $startDate = $endDate->copy()->subDays($days - 1);
        
        // Get daily aggregated data in a single query for calls
        $dailyCalls = DB::table('calls')
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->when($branch, fn($q) => $q->where('branch_id', $branch->id))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COALESCE(SUM(duration_sec * 0.02), 0) as daily_cost'),
                DB::raw('COUNT(*) as call_count')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');
            
        // Get daily aggregated data for appointments
        $dailyAppointments = DB::table('appointments')
            ->join('branches', 'appointments.branch_id', '=', 'branches.id')
            ->leftJoin('services', 'appointments.service_id', '=', 'services.id')
            ->where('branches.company_id', $company->id)
            ->whereBetween('appointments.starts_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereIn('appointments.status', ['completed', 'confirmed'])
            ->when($branch, fn($q) => $q->where('appointments.branch_id', $branch->id))
            ->select(
                DB::raw('DATE(appointments.starts_at) as date'),
                DB::raw('COALESCE(SUM(services.price), 0) as daily_revenue')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');
            
        // Build trend array
        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $endDate->copy()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            
            $dailyCost = $dailyCalls->get($dateStr)?->daily_cost ?? 0;
            $dailyRevenue = $dailyAppointments->get($dateStr)?->daily_revenue ?? 0;
            $roi = $dailyCost > 0 ? (($dailyRevenue - $dailyCost) / $dailyCost) * 100 : 0;
            
            $trend[] = [
                'date' => $date->format('d.m'),
                'roi' => round($roi, 2),
                'revenue' => $dailyRevenue,
                'costs' => $dailyCost,
            ];
        }
        
        return $trend;
    }
}