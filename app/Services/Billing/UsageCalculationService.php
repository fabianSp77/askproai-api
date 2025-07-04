<?php

namespace App\Services\Billing;

use App\Models\BillingPeriod;
use App\Models\Call;
use App\Models\Company;
use App\Models\Appointment;
use App\Models\CompanyPricing;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UsageCalculationService
{
    /**
     * Calculate usage for a billing period
     */
    public function calculatePeriodUsage(BillingPeriod $billingPeriod): array
    {
        $company = $billingPeriod->company;
        $branch = $billingPeriod->branch;
        
        // Get usage data
        $callData = $this->getCallUsage($company, $branch, $billingPeriod->start_date, $billingPeriod->end_date);
        $appointmentData = $this->getAppointmentUsage($company, $branch, $billingPeriod->start_date, $billingPeriod->end_date);
        
        // Get pricing
        $pricing = $this->getCompanyPricing($company, $billingPeriod->start_date);
        
        // Calculate costs
        $calculations = $this->calculateCosts($callData, $appointmentData, $pricing, $billingPeriod);
        
        // Update billing period
        $billingPeriod->update([
            'used_minutes' => $callData['total_minutes'],
            'total_minutes' => $callData['total_minutes'],
            'overage_minutes' => $calculations['overage_minutes'],
            'overage_cost' => $calculations['overage_cost'],
            'base_fee' => $calculations['base_fee'],
            'total_cost' => $calculations['total_cost'],
            'total_revenue' => $calculations['total_revenue'],
            'margin' => $calculations['margin'],
            'margin_percentage' => $calculations['margin_percentage']
        ]);
        
        return [
            'calls' => $callData,
            'appointments' => $appointmentData,
            'pricing' => $pricing,
            'calculations' => $calculations,
            'details' => $this->getUsageDetails($company, $branch, $billingPeriod)
        ];
    }
    
    /**
     * Get call usage statistics
     */
    protected function getCallUsage(Company $company, ?object $branch, Carbon $startDate, Carbon $endDate): array
    {
        $query = Call::where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed');
        
        if ($branch) {
            $query->where('branch_id', $branch->id);
        }
        
        $stats = $query->selectRaw('
            COUNT(*) as total_calls,
            SUM(duration_seconds) / 60 as total_minutes,
            AVG(duration_seconds) as avg_duration_seconds,
            MIN(duration_seconds) as min_duration_seconds,
            MAX(duration_seconds) as max_duration_seconds,
            COUNT(DISTINCT phone_number) as unique_callers,
            COUNT(CASE WHEN appointment_id IS NOT NULL THEN 1 END) as calls_with_appointments
        ')->first();
        
        // Get hourly distribution
        $hourlyDistribution = $query->selectRaw('
            HOUR(created_at) as hour,
            COUNT(*) as count
        ')
        ->groupBy('hour')
        ->pluck('count', 'hour')
        ->toArray();
        
        // Get daily distribution
        $dailyDistribution = $query->selectRaw('
            DATE(created_at) as date,
            COUNT(*) as calls,
            SUM(duration_seconds) / 60 as minutes
        ')
        ->groupBy('date')
        ->orderBy('date')
        ->get();
        
        return [
            'total_calls' => $stats->total_calls ?? 0,
            'total_minutes' => round($stats->total_minutes ?? 0, 2),
            'avg_duration_minutes' => round(($stats->avg_duration_seconds ?? 0) / 60, 2),
            'min_duration_minutes' => round(($stats->min_duration_seconds ?? 0) / 60, 2),
            'max_duration_minutes' => round(($stats->max_duration_seconds ?? 0) / 60, 2),
            'unique_callers' => $stats->unique_callers ?? 0,
            'calls_with_appointments' => $stats->calls_with_appointments ?? 0,
            'conversion_rate' => $stats->total_calls > 0 
                ? round(($stats->calls_with_appointments / $stats->total_calls) * 100, 2)
                : 0,
            'hourly_distribution' => $hourlyDistribution,
            'daily_distribution' => $dailyDistribution->toArray()
        ];
    }
    
    /**
     * Get appointment usage statistics
     */
    protected function getAppointmentUsage(Company $company, ?object $branch, Carbon $startDate, Carbon $endDate): array
    {
        $query = Appointment::where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        if ($branch) {
            $query->where('branch_id', $branch->id);
        }
        
        $stats = $query->selectRaw('
            COUNT(*) as total_appointments,
            COUNT(CASE WHEN status = "scheduled" THEN 1 END) as scheduled,
            COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
            COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled,
            COUNT(CASE WHEN status = "no_show" THEN 1 END) as no_shows,
            COUNT(DISTINCT customer_id) as unique_customers,
            COUNT(CASE WHEN source = "ai_phone" THEN 1 END) as ai_booked,
            COUNT(CASE WHEN source = "manual" THEN 1 END) as manual_booked
        ')->first();
        
        // Get service distribution
        $serviceDistribution = $query->selectRaw('
            service_id,
            COUNT(*) as count
        ')
        ->groupBy('service_id')
        ->with('service:id,name')
        ->get()
        ->map(function ($item) {
            return [
                'service' => $item->service->name ?? 'Unknown',
                'count' => $item->count
            ];
        });
        
        return [
            'total_appointments' => $stats->total_appointments ?? 0,
            'scheduled' => $stats->scheduled ?? 0,
            'completed' => $stats->completed ?? 0,
            'cancelled' => $stats->cancelled ?? 0,
            'no_shows' => $stats->no_shows ?? 0,
            'unique_customers' => $stats->unique_customers ?? 0,
            'ai_booked' => $stats->ai_booked ?? 0,
            'manual_booked' => $stats->manual_booked ?? 0,
            'ai_booking_rate' => $stats->total_appointments > 0
                ? round(($stats->ai_booked / $stats->total_appointments) * 100, 2)
                : 0,
            'completion_rate' => $stats->scheduled > 0
                ? round(($stats->completed / $stats->scheduled) * 100, 2)
                : 0,
            'service_distribution' => $serviceDistribution->toArray()
        ];
    }
    
    /**
     * Get company pricing for period
     */
    protected function getCompanyPricing(Company $company, Carbon $date): array
    {
        // Try to get specific pricing for this period
        $pricing = CompanyPricing::where('company_id', $company->id)
            ->where('valid_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $date);
            })
            ->orderBy('valid_from', 'desc')
            ->first();
        
        if ($pricing) {
            return [
                'model' => $pricing->pricing_model,
                'base_fee' => $pricing->base_fee,
                'included_minutes' => $pricing->included_minutes,
                'per_minute_rate' => $pricing->per_minute_rate,
                'per_appointment_rate' => $pricing->per_appointment_rate,
                'package_minutes' => $pricing->package_minutes,
                'package_appointments' => $pricing->package_appointments,
                'overage_per_minute' => $pricing->overage_per_minute ?? $pricing->per_minute_rate
            ];
        }
        
        // Default pricing if none configured
        return [
            'model' => 'per_minute',
            'base_fee' => 0,
            'included_minutes' => 0,
            'per_minute_rate' => 0.10,
            'per_appointment_rate' => 2.00,
            'package_minutes' => 0,
            'package_appointments' => 0,
            'overage_per_minute' => 0.15
        ];
    }
    
    /**
     * Calculate costs based on usage and pricing
     */
    protected function calculateCosts(array $callData, array $appointmentData, array $pricing, BillingPeriod $billingPeriod): array
    {
        $totalMinutes = $callData['total_minutes'];
        $totalAppointments = $appointmentData['total_appointments'];
        
        // Base calculations
        $baseFee = $pricing['base_fee'];
        $includedMinutes = $pricing['included_minutes'];
        $overageMinutes = max(0, $totalMinutes - $includedMinutes);
        
        // Calculate costs based on pricing model
        switch ($pricing['model']) {
            case 'per_minute':
                $minutesCost = $overageMinutes * $pricing['overage_per_minute'];
                $appointmentsCost = $totalAppointments * $pricing['per_appointment_rate'];
                break;
                
            case 'per_appointment':
                $minutesCost = 0; // Minutes included in appointment fee
                $appointmentsCost = $totalAppointments * $pricing['per_appointment_rate'];
                break;
                
            case 'package':
                // Check if within package limits
                $minutesOverPackage = max(0, $totalMinutes - $pricing['package_minutes']);
                $appointmentsOverPackage = max(0, $totalAppointments - $pricing['package_appointments']);
                
                $minutesCost = $minutesOverPackage * $pricing['overage_per_minute'];
                $appointmentsCost = $appointmentsOverPackage * $pricing['per_appointment_rate'];
                break;
                
            case 'combined':
            default:
                $minutesCost = $totalMinutes * $pricing['per_minute_rate'];
                $appointmentsCost = $totalAppointments * $pricing['per_appointment_rate'];
                break;
        }
        
        // Apply proration if needed
        if ($billingPeriod->is_prorated) {
            $baseFee = $baseFee * $billingPeriod->proration_factor;
        }
        
        // Calculate totals
        $totalCost = $baseFee + $minutesCost + $appointmentsCost;
        
        // Calculate margin (assuming 30% cost)
        $totalRevenue = $totalCost;
        $costOfService = $totalCost * 0.30; // 30% operational cost
        $margin = $totalRevenue - $costOfService;
        $marginPercentage = $totalRevenue > 0 ? ($margin / $totalRevenue) * 100 : 0;
        
        return [
            'base_fee' => round($baseFee, 2),
            'minutes_cost' => round($minutesCost, 2),
            'appointments_cost' => round($appointmentsCost, 2),
            'overage_minutes' => round($overageMinutes, 2),
            'overage_cost' => round($minutesCost, 2),
            'total_cost' => round($totalCost, 2),
            'total_revenue' => round($totalRevenue, 2),
            'cost_of_service' => round($costOfService, 2),
            'margin' => round($margin, 2),
            'margin_percentage' => round($marginPercentage, 2)
        ];
    }
    
    /**
     * Get detailed usage breakdown
     */
    protected function getUsageDetails(Company $company, ?object $branch, BillingPeriod $billingPeriod): array
    {
        // Top callers
        $topCallersQuery = Call::where('company_id', $company->id)
            ->whereBetween('created_at', [$billingPeriod->start_date, $billingPeriod->end_date])
            ->where('status', 'completed');
        
        if ($branch) {
            $topCallersQuery->where('branch_id', $branch->id);
        }
        
        $topCallers = $topCallersQuery
            ->selectRaw('
                phone_number,
                COUNT(*) as call_count,
                SUM(duration_seconds) / 60 as total_minutes
            ')
            ->groupBy('phone_number')
            ->orderByDesc('total_minutes')
            ->limit(10)
            ->get();
        
        // Peak hours
        $peakHours = Call::where('company_id', $company->id)
            ->whereBetween('created_at', [$billingPeriod->start_date, $billingPeriod->end_date])
            ->where('status', 'completed')
            ->selectRaw('
                HOUR(created_at) as hour,
                COUNT(*) as calls,
                SUM(duration_seconds) / 60 as minutes
            ')
            ->groupBy('hour')
            ->orderByDesc('calls')
            ->limit(5)
            ->get();
        
        return [
            'top_callers' => $topCallers->toArray(),
            'peak_hours' => $peakHours->toArray()
        ];
    }
    
    /**
     * Get real-time usage for current period
     */
    public function getCurrentPeriodUsage(Company $company): array
    {
        $currentPeriod = BillingPeriod::where('company_id', $company->id)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
        
        if (!$currentPeriod) {
            return [
                'error' => 'No active billing period found'
            ];
        }
        
        // Cache for 5 minutes
        return Cache::remember(
            "usage_current_{$company->id}_{$currentPeriod->id}",
            300,
            fn() => $this->calculatePeriodUsage($currentPeriod)
        );
    }
    
    /**
     * Project end-of-month usage based on current trend
     */
    public function projectMonthEndUsage(Company $company): array
    {
        $currentUsage = $this->getCurrentPeriodUsage($company);
        
        if (isset($currentUsage['error'])) {
            return $currentUsage;
        }
        
        $daysInMonth = now()->daysInMonth;
        $daysPassed = now()->day;
        $daysRemaining = $daysInMonth - $daysPassed;
        
        if ($daysPassed == 0) {
            return $currentUsage;
        }
        
        // Calculate daily averages
        $avgMinutesPerDay = $currentUsage['calls']['total_minutes'] / $daysPassed;
        $avgAppointmentsPerDay = $currentUsage['appointments']['total_appointments'] / $daysPassed;
        
        // Project totals
        $projectedMinutes = $currentUsage['calls']['total_minutes'] + ($avgMinutesPerDay * $daysRemaining);
        $projectedAppointments = $currentUsage['appointments']['total_appointments'] + ($avgAppointmentsPerDay * $daysRemaining);
        
        // Recalculate costs with projections
        $projectedCallData = $currentUsage['calls'];
        $projectedCallData['total_minutes'] = $projectedMinutes;
        
        $projectedAppointmentData = $currentUsage['appointments'];
        $projectedAppointmentData['total_appointments'] = $projectedAppointments;
        
        $projectedCosts = $this->calculateCosts(
            $projectedCallData,
            $projectedAppointmentData,
            $currentUsage['pricing'],
            BillingPeriod::find($currentUsage['details']['billing_period_id'] ?? 0)
        );
        
        return [
            'current' => $currentUsage,
            'projected' => [
                'minutes' => round($projectedMinutes, 2),
                'appointments' => round($projectedAppointments, 0),
                'costs' => $projectedCosts,
                'days_remaining' => $daysRemaining,
                'avg_per_day' => [
                    'minutes' => round($avgMinutesPerDay, 2),
                    'appointments' => round($avgAppointmentsPerDay, 2)
                ]
            ]
        ];
    }
}