<?php

namespace App\Services\Dashboard;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use App\Models\PhoneNumber;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Zentrale Service-Klasse für Dashboard KPI-Berechnungen
 * 
 * Bietet einheitliche, cachende KPI-Berechnungen für alle Dashboard-Widgets
 * Performance-optimiert mit Multi-Level-Caching und Fallback-Strategien
 */
class DashboardMetricsService
{
    const CACHE_TTL_LIVE = 60;      // 1 Minute für Live-KPIs
    const CACHE_TTL_HOURLY = 300;   // 5 Minuten für stündliche Trends
    const CACHE_TTL_DAILY = 3600;   // 1 Stunde für tägliche Aggregationen
    
    /**
     * Company ID for filtering
     */
    protected ?int $companyId = null;
    
    /**
     * Company phone numbers cache
     */
    protected ?array $companyPhoneNumbers = null;

    /**
     * Berechnet alle KPIs für die Termine-Seite
     */
    public function getAppointmentKpis(array $filters = []): array
    {
        $cacheKey = 'appointment_kpis_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_TTL_LIVE, function() use ($filters) {
            try {
                $dateRange = $this->getDateRange($filters);
                $previousRange = $this->getPreviousDateRange($dateRange);
                $companyId = $filters['company_id'] ?? null;
                
                return [
                    'revenue' => $this->calculateAppointmentRevenue($dateRange, $previousRange, $filters),
                    'appointments' => $this->calculateAppointmentCount($dateRange, $previousRange, $filters),
                    'occupancy' => $this->calculateOccupancy($dateRange, $previousRange),
                    'conversion' => $this->calculateConversionRate($dateRange, $previousRange, $companyId),
                    'no_show_rate' => $this->calculateNoShowRate($dateRange, $previousRange),
                    'avg_duration' => $this->calculateAvgDuration($dateRange, $previousRange),
                    'revenue_per_appointment' => $this->calculateRevenuePerAppointment($dateRange, $previousRange),
                ];
            } catch (\Exception $e) {
                Log::error('Appointment KPIs calculation failed', ['error' => $e->getMessage(), 'filters' => $filters]);
                return $this->getEmptyAppointmentKpis();
            }
        });
    }

    /**
     * Berechnet alle KPIs für die Anrufe-Seite
     */
    public function getCallKpis(array $filters = []): array
    {
        $cacheKey = 'call_kpis_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_TTL_LIVE, function() use ($filters) {
            try {
                $dateRange = $this->getDateRange($filters);
                $previousRange = $this->getPreviousDateRange($dateRange);
                $companyId = $filters['company_id'] ?? null;
                
                return [
                    'total_calls' => $this->calculateTotalCalls($dateRange, $previousRange, $companyId),
                    'avg_duration' => $this->calculateCallAvgDuration($dateRange, $previousRange, $companyId),
                    'success_rate' => $this->calculateCallSuccessRate($dateRange, $previousRange, $companyId),
                    'sentiment_positive' => $this->calculatePositiveSentiment($dateRange, $previousRange, $companyId),
                    'avg_cost' => $this->calculateAvgCallCost($dateRange, $previousRange, $companyId),
                    'roi' => $this->calculateCallROI($dateRange, $previousRange, $companyId),
                ];
            } catch (\Exception $e) {
                Log::error('Call KPIs calculation failed', ['error' => $e->getMessage(), 'filters' => $filters]);
                return $this->getEmptyCallKpis();
            }
        });
    }

    /**
     * Berechnet alle KPIs für die Kunden-Seite
     */
    public function getCustomerKpis(array $filters = []): array
    {
        $cacheKey = 'customer_kpis_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_TTL_LIVE, function() use ($filters) {
            try {
                $dateRange = $this->getDateRange($filters);
                $previousRange = $this->getPreviousDateRange($dateRange);
                $companyId = $filters['company_id'] ?? null;
                
                return [
                    'total_customers' => $this->calculateTotalCustomers($dateRange, $previousRange, $companyId),
                    'new_customers' => $this->calculateNewCustomers($dateRange, $previousRange, $companyId),
                    'avg_clv' => $this->calculateAvgCustomerLifetimeValue($dateRange, $previousRange),
                    'returning_rate' => $this->calculateReturningCustomerRate($dateRange, $previousRange),
                    'churn_rate' => $this->calculateChurnRate($dateRange, $previousRange),
                    'top_customers_revenue' => $this->calculateTopCustomersRevenue($dateRange, $previousRange),
                ];
            } catch (\Exception $e) {
                Log::error('Customer KPIs calculation failed', ['error' => $e->getMessage(), 'filters' => $filters]);
                return $this->getEmptyCustomerKpis();
            }
        });
    }

    /**
     * Berechnet Trend-Daten für Charts
     */
    public function getTrendData(string $metric, string $period = '30d', array $filters = []): array
    {
        $cacheKey = "trend_{$metric}_{$period}_" . md5(serialize($filters));
        
        return Cache::remember($cacheKey, self::CACHE_TTL_HOURLY, function() use ($metric, $period, $filters) {
            try {
                $dates = $this->getDateRangeForPeriod($period);
                
                return match($metric) {
                    'revenue' => $this->getRevenueTrend($dates, $filters),
                    'appointments' => $this->getAppointmentsTrend($dates, $filters),
                    'calls' => $this->getCallsTrend($dates, $filters),
                    'customers' => $this->getCustomersTrend($dates, $filters),
                    default => [],
                };
            } catch (\Exception $e) {
                Log::error('Trend data calculation failed', ['metric' => $metric, 'error' => $e->getMessage()]);
                return [];
            }
        });
    }

    // ========================================================================
    // APPOINTMENT KPI CALCULATIONS
    // ========================================================================

    private function calculateAppointmentRevenue(array $dateRange, array $previousRange, array $filters = []): array
    {
        // Start with base query
        $query = Appointment::query();
        
        // If company_id is provided, remove global scope and filter manually
        if (!empty($filters['company_id'])) {
            $query = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('appointments.company_id', $filters['company_id']);
        }
            
        $query->join('services', 'appointments.service_id', '=', 'services.id')
            ->where('appointments.status', 'completed');
            
        // Apply branch filter if provided
        if (!empty($filters['branch_id'])) {
            $query->where('appointments.branch_id', $filters['branch_id']);
        }
            
        $current = $query->clone()
            ->whereBetween('appointments.starts_at', $dateRange)
            ->sum('services.price') ?? 0;

        $previous = $query->clone()
            ->whereBetween('appointments.starts_at', $previousRange)
            ->sum('services.price') ?? 0;

        return [
            'value' => $current,
            'previous' => $previous,
            'change' => $this->calculatePercentageChange($current, $previous, 2),
            'formatted' => number_format($current, 0, ',', '.') . '€',
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }
    
    private function calculateAppointmentCount(array $dateRange, array $previousRange, array $filters = []): array
    {
        // Start with base query
        $query = Appointment::query();
        
        // If company_id is provided, remove global scope and filter manually
        if (!empty($filters['company_id'])) {
            $query = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('company_id', $filters['company_id']);
        }
        
        // Apply branch filter if provided
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        
        $current = $query->clone()
            ->whereBetween('starts_at', $dateRange)
            ->count();
            
        $previous = $query->clone()
            ->whereBetween('starts_at', $previousRange)
            ->count();
            
        return [
            'value' => $current,
            'previous' => $previous,
            'change' => $this->calculatePercentageChange($current, $previous),
            'formatted' => number_format($current, 0, ',', '.'),
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    private function calculateOccupancy(array $dateRange, array $previousRange): array
    {
        // Vereinfachte Berechnung - in Realität würde hier Working Hours berücksichtigt werden
        $totalSlots = $this->estimateAvailableSlots($dateRange);
        $bookedSlots = Appointment::whereBetween('starts_at', $dateRange)->count();
        
        $current = $totalSlots > 0 ? ($bookedSlots / $totalSlots) * 100 : 0;
        
        $prevTotalSlots = $this->estimateAvailableSlots($previousRange);
        $prevBookedSlots = Appointment::whereBetween('starts_at', $previousRange)->count();
        $previous = $prevTotalSlots > 0 ? ($prevBookedSlots / $prevTotalSlots) * 100 : 0;

        return [
            'value' => round($current, 1),
            'previous' => round($previous, 1),
            'change' => $this->calculatePercentageChange($current, $previous, 1),
            'formatted' => round($current, 1) . '%',
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    private function calculateConversionRate(array $dateRange, array $previousRange, ?int $companyId = null): array
    {
        // Use forCompany() to explicitly set company context
        $query = $companyId ? Call::forCompany($companyId) : Call::query();
        
        if ($companyId) {
            $phoneNumbers = $this->getCompanyPhoneNumbers($companyId);
            if (!empty($phoneNumbers)) {
                $query->whereIn('to_number', $phoneNumbers);
            }
        }
        
        $callsWithIntentQuery = $query->clone()
            ->whereBetween('created_at', $dateRange);
            
        // Apply JSON query differently for SQLite vs MySQL
        if (DB::connection()->getDriverName() === 'sqlite') {
            $callsWithIntentQuery->where(function($q) {
                $q->whereRaw("json_extract(analysis, '$.intent') LIKE ?", ['%booking%'])
                  ->orWhere('duration_sec', '>', 30);
            });
        } else {
            $callsWithIntentQuery->where(function($q) {
                $q->whereJsonContains('analysis->intent', 'booking')
                  ->orWhere('duration_sec', '>', 30);
            });
        }
        
        $callsWithIntent = $callsWithIntentQuery->count();

        $appointmentsFromCalls = Appointment::whereNotNull('call_id')
            ->whereBetween('created_at', $dateRange)
            ->when($companyId, function($q) use ($companyId) {
                $q->whereHas('call', function($callQuery) use ($companyId) {
                    $phoneNumbers = $this->getCompanyPhoneNumbers($companyId);
                    if (!empty($phoneNumbers)) {
                        $callQuery->whereIn('to_number', $phoneNumbers);
                    }
                    $callQuery->where('company_id', $companyId);
                });
            })
            ->count();

        $current = $callsWithIntent > 0 ? ($appointmentsFromCalls / $callsWithIntent) * 100 : 0;

        // Previous period
        $prevCallsWithIntentQuery = $query->clone()
            ->whereBetween('created_at', $previousRange);
            
        // Apply JSON query differently for SQLite vs MySQL
        if (DB::connection()->getDriverName() === 'sqlite') {
            $prevCallsWithIntentQuery->where(function($q) {
                $q->whereRaw("json_extract(analysis, '$.intent') LIKE ?", ['%booking%'])
                  ->orWhere('duration_sec', '>', 30);
            });
        } else {
            $prevCallsWithIntentQuery->where(function($q) {
                $q->whereJsonContains('analysis->intent', 'booking')
                  ->orWhere('duration_sec', '>', 30);
            });
        }
        
        $prevCallsWithIntent = $prevCallsWithIntentQuery->count();

        $prevAppointmentsFromCalls = Appointment::whereNotNull('call_id')
            ->whereBetween('created_at', $previousRange)
            ->when($companyId, function($q) use ($companyId) {
                $q->whereHas('call', function($callQuery) use ($companyId) {
                    $phoneNumbers = $this->getCompanyPhoneNumbers($companyId);
                    if (!empty($phoneNumbers)) {
                        $callQuery->whereIn('to_number', $phoneNumbers);
                    }
                    $callQuery->where('company_id', $companyId);
                });
            })
            ->count();

        $previous = $prevCallsWithIntent > 0 ? ($prevAppointmentsFromCalls / $prevCallsWithIntent) * 100 : 0;

        return [
            'value' => round($current, 1),
            'previous' => round($previous, 1),
            'change' => $this->calculatePercentageChange($current, $previous, 1),
            'formatted' => round($current, 1) . '%',
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    private function calculateNoShowRate(array $dateRange, array $previousRange): array
    {
        $totalAppointments = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('starts_at', $dateRange)->count();
        $noShows = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('status', 'no_show')
            ->whereBetween('starts_at', $dateRange)
            ->count();
        
        $current = $totalAppointments > 0 ? ($noShows / $totalAppointments) * 100 : 0;

        // Previous period
        $prevTotalAppointments = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('starts_at', $previousRange)->count();
        $prevNoShows = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('status', 'no_show')
            ->whereBetween('starts_at', $previousRange)
            ->count();
        
        $previous = $prevTotalAppointments > 0 ? ($prevNoShows / $prevTotalAppointments) * 100 : 0;

        return [
            'value' => round($current, 1),
            'previous' => round($previous, 1),
            'change' => $this->calculatePercentageChange($current, $previous, 1),
            'formatted' => round($current, 1) . '%',
            'trend' => $current < $previous ? 'up' : ($current > $previous ? 'down' : 'stable'), // Niedrigere No-Show Rate ist besser
        ];
    }

    private function calculateAvgDuration(array $dateRange, array $previousRange): array
    {
        $current = Appointment::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereBetween('starts_at', $dateRange)
            ->whereNotNull('duration_minutes')
            ->avg('duration_minutes') ?? 0;

        $previous = Appointment::whereBetween('starts_at', $previousRange)
            ->whereNotNull('duration_minutes')
            ->avg('duration_minutes') ?? 0;

        return [
            'value' => round($current, 0),
            'previous' => round($previous, 0),
            'change' => round($current - $previous, 0),
            'formatted' => round($current, 0) . ' min',
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    private function calculateRevenuePerAppointment(array $dateRange, array $previousRange): array
    {
        $totalRevenue = Appointment::query()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->where('appointments.status', 'completed')
            ->whereBetween('appointments.starts_at', $dateRange)
            ->sum('services.price') ?? 0;

        $totalAppointments = Appointment::where('status', 'completed')
            ->whereBetween('starts_at', $dateRange)
            ->count();

        $current = $totalAppointments > 0 ? $totalRevenue / $totalAppointments : 0;

        // Previous period
        $prevTotalRevenue = Appointment::query()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->where('appointments.status', 'completed')
            ->whereBetween('appointments.starts_at', $previousRange)
            ->sum('services.price') ?? 0;

        $prevTotalAppointments = Appointment::where('status', 'completed')
            ->whereBetween('starts_at', $previousRange)
            ->count();

        $previous = $prevTotalAppointments > 0 ? $prevTotalRevenue / $prevTotalAppointments : 0;

        return [
            'value' => round($current, 0),
            'previous' => round($previous, 0),
            'change' => $this->calculatePercentageChange($current, $previous),
            'formatted' => number_format($current, 0, ',', '.') . '€',
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    // ========================================================================
    // CALL KPI CALCULATIONS
    // ========================================================================

    private function calculateTotalCalls(array $dateRange, array $previousRange, ?int $companyId = null): array
    {
        // Use forCompany() to explicitly set company context
        $query = $companyId ? Call::forCompany($companyId) : Call::query();
        
        if ($companyId) {
            $phoneNumbers = $this->getCompanyPhoneNumbers($companyId);
            if (!empty($phoneNumbers)) {
                $query->whereIn('to_number', $phoneNumbers);
            }
        }
        
        $current = $query->clone()->whereBetween('created_at', $dateRange)->count();
        $previous = $query->clone()->whereBetween('created_at', $previousRange)->count();

        return [
            'value' => $current,
            'previous' => $previous,
            'change' => $current - $previous,
            'formatted' => number_format($current, 0, ',', '.'),
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    private function calculateCallAvgDuration(array $dateRange, array $previousRange, ?int $companyId = null): array
    {
        // Use forCompany() to explicitly set company context
        $query = $companyId ? Call::forCompany($companyId) : Call::query();
        
        if ($companyId) {
            $phoneNumbers = $this->getCompanyPhoneNumbers($companyId);
            if (!empty($phoneNumbers)) {
                $query->whereIn('to_number', $phoneNumbers);
            }
        }
        
        $current = $query->clone()
            ->whereBetween('created_at', $dateRange)
            ->whereNotNull('duration_sec')
            ->avg('duration_sec') ?? 0;

        $previous = $query->clone()
            ->whereBetween('created_at', $previousRange)
            ->whereNotNull('duration_sec')
            ->avg('duration_sec') ?? 0;

        return [
            'value' => $current,
            'previous' => $previous,
            'change' => round($current - $previous, 0),
            'formatted' => $this->formatDuration($current),
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    private function calculateCallSuccessRate(array $dateRange, array $previousRange, ?int $companyId = null): array
    {
        // Use forCompany() to explicitly set company context
        $query = $companyId ? Call::forCompany($companyId) : Call::query();
        
        if ($companyId) {
            $phoneNumbers = $this->getCompanyPhoneNumbers($companyId);
            if (!empty($phoneNumbers)) {
                $query->whereIn('to_number', $phoneNumbers);
            }
        }
        
        $totalCalls = $query->clone()->whereBetween('created_at', $dateRange)->count();
        $successfulCalls = $query->clone()
            ->whereBetween('created_at', $dateRange)
            ->whereHas('appointment')
            ->count();

        $current = $totalCalls > 0 ? ($successfulCalls / $totalCalls) * 100 : 0;

        // Previous period
        $prevTotalCalls = $query->clone()->whereBetween('created_at', $previousRange)->count();
        $prevSuccessfulCalls = $query->clone()
            ->whereBetween('created_at', $previousRange)
            ->whereHas('appointment')
            ->count();

        $previous = $prevTotalCalls > 0 ? ($prevSuccessfulCalls / $prevTotalCalls) * 100 : 0;

        return [
            'value' => round($current, 1),
            'previous' => round($previous, 1),
            'change' => $this->calculatePercentageChange($current, $previous, 1),
            'formatted' => round($current, 1) . '%',
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    private function calculatePositiveSentiment(array $dateRange, array $previousRange, ?int $companyId = null): array
    {
        // Use forCompany() to explicitly set company context
        $query = $companyId ? Call::forCompany($companyId) : Call::query();
        
        if ($companyId) {
            $phoneNumbers = $this->getCompanyPhoneNumbers($companyId);
            if (!empty($phoneNumbers)) {
                $query->whereIn('to_number', $phoneNumbers);
            }
        }
        
        $totalCallsQuery = $query->clone()
            ->whereBetween('created_at', $dateRange);
            
        $positiveCallsQuery = $query->clone()
            ->whereBetween('created_at', $dateRange);
            
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite: Check if sentiment exists and is one of the valid values
            $totalCallsQuery->whereRaw("json_extract(analysis, '$.sentiment') IN ('positive', 'negative', 'neutral')");
            $positiveCallsQuery->whereRaw("json_extract(analysis, '$.sentiment') = 'positive'");
        } else {
            // MySQL: Use native JSON functions
            $totalCallsQuery->whereJsonContains('analysis->sentiment', ['positive', 'negative', 'neutral']);
            $positiveCallsQuery->whereJsonContains('analysis->sentiment', 'positive');
        }
        
        $totalCalls = $totalCallsQuery->count();
        $positiveCalls = $positiveCallsQuery->count();

        $current = $totalCalls > 0 ? ($positiveCalls / $totalCalls) * 100 : 0;

        // Previous period
        $prevTotalCallsQuery = $query->clone()
            ->whereBetween('created_at', $previousRange);
            
        $prevPositiveCallsQuery = $query->clone()
            ->whereBetween('created_at', $previousRange);
            
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite: Check if sentiment exists and is one of the valid values
            $prevTotalCallsQuery->whereRaw("json_extract(analysis, '$.sentiment') IN ('positive', 'negative', 'neutral')");
            $prevPositiveCallsQuery->whereRaw("json_extract(analysis, '$.sentiment') = 'positive'");
        } else {
            // MySQL: Use native JSON functions
            $prevTotalCallsQuery->whereJsonContains('analysis->sentiment', ['positive', 'negative', 'neutral']);
            $prevPositiveCallsQuery->whereJsonContains('analysis->sentiment', 'positive');
        }
        
        $prevTotalCalls = $prevTotalCallsQuery->count();
        $prevPositiveCalls = $prevPositiveCallsQuery->count();

        $previous = $prevTotalCalls > 0 ? ($prevPositiveCalls / $prevTotalCalls) * 100 : 0;

        return [
            'value' => round($current, 1),
            'previous' => round($previous, 1),
            'change' => $this->calculatePercentageChange($current, $previous, 1),
            'formatted' => round($current, 1) . '%',
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    private function calculateAvgCallCost(array $dateRange, array $previousRange, ?int $companyId = null): array
    {
        // Use forCompany() to explicitly set company context
        $query = $companyId ? Call::forCompany($companyId) : Call::query();
        
        if ($companyId) {
            $phoneNumbers = $this->getCompanyPhoneNumbers($companyId);
            if (!empty($phoneNumbers)) {
                $query->whereIn('to_number', $phoneNumbers);
            }
        }
        
        $current = $query->clone()
            ->whereBetween('created_at', $dateRange)
            ->whereNotNull('cost')
            ->avg('cost') ?? 0;

        $previous = $query->clone()
            ->whereBetween('created_at', $previousRange)
            ->whereNotNull('cost')
            ->avg('cost') ?? 0;

        return [
            'value' => $current,
            'previous' => $previous,
            'change' => round($current - $previous, 2),
            'formatted' => number_format($current, 2, ',', '.') . '€',
            'trend' => $current < $previous ? 'up' : ($current > $previous ? 'down' : 'stable'), // Niedrigere Kosten sind besser
        ];
    }

    private function calculateCallROI(array $dateRange, array $previousRange, ?int $companyId = null): array
    {
        // ROI = (Termin-Wert - Call-Kosten) / Call-Kosten * 100
        // Use forCompany() to explicitly set company context
        $query = $companyId ? Call::forCompany($companyId) : Call::query();
        
        if ($companyId) {
            $phoneNumbers = $this->getCompanyPhoneNumbers($companyId);
            if (!empty($phoneNumbers)) {
                $query->whereIn('to_number', $phoneNumbers);
            }
        }
        
        $callCosts = $query->clone()
            ->whereBetween('created_at', $dateRange)
            ->whereHas('appointment')
            ->sum('cost') ?? 0;

        $appointmentRevenue = Appointment::query()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->whereNotNull('appointments.call_id')
            ->whereBetween('appointments.created_at', $dateRange)
            ->when($companyId, function($q) use ($companyId) {
                $q->whereHas('call', function($callQuery) use ($companyId) {
                    $phoneNumbers = $this->getCompanyPhoneNumbers($companyId);
                    if (!empty($phoneNumbers)) {
                        $callQuery->whereIn('to_number', $phoneNumbers);
                    }
                    $callQuery->where('company_id', $companyId);
                });
            })
            ->sum('services.price') ?? 0;

        $current = $callCosts > 0 ? (($appointmentRevenue - $callCosts) / $callCosts) * 100 : 0;

        // Previous period
        $prevCallCosts = $query->clone()
            ->whereBetween('created_at', $previousRange)
            ->whereHas('appointment')
            ->sum('cost') ?? 0;

        $prevAppointmentRevenue = Appointment::query()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->whereNotNull('appointments.call_id')
            ->whereBetween('appointments.created_at', $previousRange)
            ->when($companyId, function($q) use ($companyId) {
                $q->whereHas('call', function($callQuery) use ($companyId) {
                    $phoneNumbers = $this->getCompanyPhoneNumbers($companyId);
                    if (!empty($phoneNumbers)) {
                        $callQuery->whereIn('to_number', $phoneNumbers);
                    }
                    $callQuery->where('company_id', $companyId);
                });
            })
            ->sum('services.price') ?? 0;

        $previous = $prevCallCosts > 0 ? (($prevAppointmentRevenue - $prevCallCosts) / $prevCallCosts) * 100 : 0;

        return [
            'value' => round($current, 1),
            'previous' => round($previous, 1),
            'change' => $this->calculatePercentageChange($current, $previous, 1),
            'formatted' => round($current, 1) . '%',
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    // ========================================================================
    // CUSTOMER KPI CALCULATIONS
    // ========================================================================

    private function calculateTotalCustomers(array $dateRange, array $previousRange, ?int $companyId = null): array
    {
        // Use withoutGlobalScope if company_id is provided
        $query = $companyId 
            ? Customer::withoutGlobalScope(\App\Scopes\TenantScope::class)->where('company_id', $companyId)
            : Customer::query();
            
        $current = $query->count();
        $previous = $query->clone()->where('created_at', '<', $previousRange[1])->count();

        return [
            'value' => $current,
            'previous' => $previous,
            'change' => $current - $previous,
            'formatted' => number_format($current, 0, ',', '.'),
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    private function calculateNewCustomers(array $dateRange, array $previousRange, ?int $companyId = null): array
    {
        // Use withoutGlobalScope if company_id is provided
        $query = $companyId 
            ? Customer::withoutGlobalScope(\App\Scopes\TenantScope::class)->where('company_id', $companyId)
            : Customer::query();
            
        $current = $query->clone()->whereBetween('created_at', $dateRange)->count();
        $previous = $query->clone()->whereBetween('created_at', $previousRange)->count();

        return [
            'value' => $current,
            'previous' => $previous,
            'change' => $current - $previous,
            'formatted' => number_format($current, 0, ',', '.'),
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    private function calculateAvgCustomerLifetimeValue(array $dateRange, array $previousRange): array
    {
        // CLV für Kunden die im Zeitraum erstellt wurden
        $current = Customer::query()
            ->whereBetween('created_at', $dateRange)
            ->withSum(['appointments' => function($q) {
                $q->join('services', 'appointments.service_id', '=', 'services.id')
                  ->where('appointments.status', 'completed');
            }], 'services.price')
            ->avg('appointments_sum_servicesprice') ?? 0;

        // Previous Period
        $previous = Customer::query()
            ->whereBetween('created_at', $previousRange)
            ->withSum(['appointments' => function($q) {
                $q->join('services', 'appointments.service_id', '=', 'services.id')
                  ->where('appointments.status', 'completed');
            }], 'services.price')
            ->avg('appointments_sum_servicesprice') ?? 0;

        return [
            'value' => round($current, 0),
            'previous' => round($previous, 0),
            'change' => $this->calculatePercentageChange($current, $previous),
            'formatted' => number_format($current, 0, ',', '.') . '€',
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    private function calculateReturningCustomerRate(array $dateRange, array $previousRange): array
    {
        // Kunden mit Terminen im aktuellen Zeitraum
        $totalCustomers = Customer::whereHas('appointments', function($q) use ($dateRange) {
            $q->whereBetween('starts_at', $dateRange);
        })->count();
        
        // Davon: Kunden mit mehr als einem Termin insgesamt
        $returningCustomers = Customer::whereHas('appointments', function($q) use ($dateRange) {
            $q->whereBetween('starts_at', $dateRange);
        })->has('appointments', '>=', 2)->count();

        $current = $totalCustomers > 0 ? ($returningCustomers / $totalCustomers) * 100 : 0;

        // Previous Period
        $prevTotalCustomers = Customer::whereHas('appointments', function($q) use ($previousRange) {
            $q->whereBetween('starts_at', $previousRange);
        })->count();
        
        $prevReturningCustomers = Customer::whereHas('appointments', function($q) use ($previousRange) {
            $q->whereBetween('starts_at', $previousRange);
        })->has('appointments', '>=', 2)->count();

        $previous = $prevTotalCustomers > 0 ? ($prevReturningCustomers / $prevTotalCustomers) * 100 : 0;

        return [
            'value' => round($current, 1),
            'previous' => round($previous, 1),
            'change' => $this->calculatePercentageChange($current, $previous, 1),
            'formatted' => round($current, 1) . '%',
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    private function calculateChurnRate(array $dateRange, array $previousRange): array
    {
        // Aktive Kunden am Ende des Zeitraums
        $endDate = Carbon::parse($dateRange[1]);
        $activeCustomers = Customer::whereHas('appointments', function($q) use ($endDate) {
            $q->where('starts_at', '<=', $endDate);
        })->count();
        
        // Davon: Kunden ohne Termin in den letzten 90 Tagen vom Ende des Zeitraums
        $churnedCustomers = Customer::whereHas('appointments', function($q) use ($endDate) {
            $q->where('starts_at', '<=', $endDate);
        })->whereDoesntHave('appointments', function($q) use ($endDate) {
            $q->where('starts_at', '>', $endDate->copy()->subDays(90))
              ->where('starts_at', '<=', $endDate);
        })->count();

        $current = $activeCustomers > 0 ? ($churnedCustomers / $activeCustomers) * 100 : 0;

        // Previous Period
        $prevEndDate = Carbon::parse($previousRange[1]);
        $prevActiveCustomers = Customer::whereHas('appointments', function($q) use ($prevEndDate) {
            $q->where('starts_at', '<=', $prevEndDate);
        })->count();
        
        $prevChurnedCustomers = Customer::whereHas('appointments', function($q) use ($prevEndDate) {
            $q->where('starts_at', '<=', $prevEndDate);
        })->whereDoesntHave('appointments', function($q) use ($prevEndDate) {
            $q->where('starts_at', '>', $prevEndDate->copy()->subDays(90))
              ->where('starts_at', '<=', $prevEndDate);
        })->count();

        $previous = $prevActiveCustomers > 0 ? ($prevChurnedCustomers / $prevActiveCustomers) * 100 : 0;

        return [
            'value' => round($current, 1),
            'previous' => round($previous, 1),
            'change' => $this->calculatePercentageChange($current, $previous, 1),
            'formatted' => round($current, 1) . '%',
            'trend' => $current < $previous ? 'up' : ($current > $previous ? 'down' : 'stable'), // Niedrigere Churn Rate ist besser
        ];
    }

    private function calculateTopCustomersRevenue(array $dateRange, array $previousRange): array
    {
        // Top 10 Kunden-Umsatz im Zeitraum
        $topCustomerIds = Customer::query()
            ->join('appointments', 'customers.id', '=', 'appointments.customer_id')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->where('appointments.status', 'completed')
            ->whereBetween('appointments.starts_at', $dateRange)
            ->groupBy('customers.id')
            ->orderByRaw('SUM(services.price) DESC')
            ->limit(10)
            ->pluck('customers.id');

        $topCustomersRevenue = Appointment::query()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->whereIn('appointments.customer_id', $topCustomerIds)
            ->where('appointments.status', 'completed')
            ->whereBetween('appointments.starts_at', $dateRange)
            ->sum('services.price') ?? 0;

        $totalRevenue = Appointment::query()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->where('appointments.status', 'completed')
            ->whereBetween('appointments.starts_at', $dateRange)
            ->sum('services.price') ?? 0;

        $current = $totalRevenue > 0 ? ($topCustomersRevenue / $totalRevenue) * 100 : 0;

        // Previous Period
        $prevTopCustomerIds = Customer::query()
            ->join('appointments', 'customers.id', '=', 'appointments.customer_id')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->where('appointments.status', 'completed')
            ->whereBetween('appointments.starts_at', $previousRange)
            ->groupBy('customers.id')
            ->orderByRaw('SUM(services.price) DESC')
            ->limit(10)
            ->pluck('customers.id');

        $prevTopCustomersRevenue = Appointment::query()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->whereIn('appointments.customer_id', $prevTopCustomerIds)
            ->where('appointments.status', 'completed')
            ->whereBetween('appointments.starts_at', $previousRange)
            ->sum('services.price') ?? 0;

        $prevTotalRevenue = Appointment::query()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->where('appointments.status', 'completed')
            ->whereBetween('appointments.starts_at', $previousRange)
            ->sum('services.price') ?? 0;

        $previous = $prevTotalRevenue > 0 ? ($prevTopCustomersRevenue / $prevTotalRevenue) * 100 : 0;

        return [
            'value' => round($current, 1),
            'previous' => round($previous, 1),
            'change' => $this->calculatePercentageChange($current, $previous, 1),
            'formatted' => round($current, 1) . '%',
            'trend' => $current > $previous ? 'up' : ($current < $previous ? 'down' : 'stable'),
        ];
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================
    
    /**
     * Get company phone numbers for filtering
     */
    private function getCompanyPhoneNumbers(?int $companyId): array
    {
        if (!$companyId) {
            return [];
        }
        
        $query = PhoneNumber::where('company_id', $companyId);
        
        // Only filter by is_active if the column exists (for compatibility with tests)
        if (\Schema::hasColumn('phone_numbers', 'is_active')) {
            $query->where('is_active', true);
        }
        
        return $query->pluck('number')->toArray();
    }

    private function getDateRange(array $filters): array
    {
        $period = $filters['period'] ?? 'today';
        
        // Handle custom date range
        if ($period === 'custom' && isset($filters['date_from']) && isset($filters['date_to'])) {
            return [
                Carbon::parse($filters['date_from'])->startOfDay(),
                Carbon::parse($filters['date_to'])->endOfDay(),
            ];
        }
        
        return match($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->yesterday()->startOfDay(), now()->yesterday()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    private function getPreviousDateRange(array $currentRange): array
    {
        $start = Carbon::parse($currentRange[0]);
        $end = Carbon::parse($currentRange[1]);
        
        // For single-day ranges (like "today"), return the full previous day
        if ($start->isSameDay($end)) {
            return [
                $start->copy()->subDay()->startOfDay(),
                $start->copy()->subDay()->endOfDay(),
            ];
        }
        
        // For multi-day ranges, use the original logic
        $duration = $start->diffInDays($end);
        
        return [
            $start->copy()->subDays($duration + 1),
            $start->copy()->subDay(),
        ];
    }

    private function calculatePercentageChange(float $current, float $previous, int $decimals = 0): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, $decimals);
    }

    private function formatDuration(float $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return sprintf('%d:%02d min', $minutes, $remainingSeconds);
    }

    private function estimateAvailableSlots(array $dateRange): int
    {
        // Vereinfachte Schätzung: 8 Stunden * 4 Slots pro Stunde * Anzahl Tage
        $days = Carbon::parse($dateRange[0])->diffInDays(Carbon::parse($dateRange[1])) + 1;
        return $days * 8 * 4; // 32 Slots pro Tag
    }

    private function getDateRangeForPeriod(string $period): array
    {
        return match($period) {
            '7d' => collect(range(6, 0))->map(fn($i) => now()->subDays($i)->format('Y-m-d'))->toArray(),
            '30d' => collect(range(29, 0))->map(fn($i) => now()->subDays($i)->format('Y-m-d'))->toArray(),
            '90d' => collect(range(89, 0))->map(fn($i) => now()->subDays($i)->format('Y-m-d'))->toArray(),
            default => collect(range(29, 0))->map(fn($i) => now()->subDays($i)->format('Y-m-d'))->toArray(),
        };
    }

    private function getRevenueTrend(array $dates, array $filters): array
    {
        $data = [];
        
        foreach ($dates as $date) {
            $revenue = Appointment::query()
                ->join('services', 'appointments.service_id', '=', 'services.id')
                ->where('appointments.status', 'completed')
                ->whereDate('appointments.starts_at', $date)
                ->sum('services.price') ?? 0;
                
            $data[] = [
                'date' => $date,
                'value' => $revenue,
                'formatted' => number_format($revenue, 0, ',', '.') . '€',
            ];
        }
        
        return $data;
    }

    private function getAppointmentsTrend(array $dates, array $filters): array
    {
        $data = [];
        
        foreach ($dates as $date) {
            $count = Appointment::whereDate('starts_at', $date)->count();
            
            $data[] = [
                'date' => $date,
                'value' => $count,
                'formatted' => $count,
            ];
        }
        
        return $data;
    }

    private function getCallsTrend(array $dates, array $filters): array
    {
        $data = [];
        
        foreach ($dates as $date) {
            $count = Call::whereDate('created_at', $date)->count();
            
            $data[] = [
                'date' => $date,
                'value' => $count,
                'formatted' => $count,
            ];
        }
        
        return $data;
    }

    private function getCustomersTrend(array $dates, array $filters): array
    {
        $data = [];
        
        foreach ($dates as $date) {
            $count = Customer::whereDate('created_at', $date)->count();
            
            $data[] = [
                'date' => $date,
                'value' => $count,
                'formatted' => $count,
            ];
        }
        
        return $data;
    }

    private function getEmptyAppointmentKpis(): array
    {
        return [
            'revenue' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0€', 'trend' => 'stable'],
            'appointments' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0', 'trend' => 'stable'],
            'occupancy' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0%', 'trend' => 'stable'],
            'conversion' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0%', 'trend' => 'stable'],
            'no_show_rate' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0%', 'trend' => 'stable'],
            'avg_duration' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0 min', 'trend' => 'stable'],
            'revenue_per_appointment' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0€', 'trend' => 'stable'],
        ];
    }

    private function getEmptyCallKpis(): array
    {
        return [
            'total_calls' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0', 'trend' => 'stable'],
            'avg_duration' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0:00 min', 'trend' => 'stable'],
            'success_rate' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0%', 'trend' => 'stable'],
            'sentiment_positive' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0%', 'trend' => 'stable'],
            'avg_cost' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0€', 'trend' => 'stable'],
            'roi' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0%', 'trend' => 'stable'],
        ];
    }

    private function getEmptyCustomerKpis(): array
    {
        return [
            'total_customers' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0', 'trend' => 'stable'],
            'new_customers' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0', 'trend' => 'stable'],
            'avg_clv' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0€', 'trend' => 'stable'],
            'returning_rate' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0%', 'trend' => 'stable'],
            'churn_rate' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0%', 'trend' => 'stable'],
            'top_customers_revenue' => ['value' => 0, 'previous' => 0, 'change' => 0, 'formatted' => '0%', 'trend' => 'stable'],
        ];
    }
    
    // ========================================================================
    // NEW METHODS FOR EXECUTIVE DASHBOARD
    // ========================================================================
    
    /**
     * Get operational metrics for executive dashboard
     */
    public function getOperationalMetrics(?\App\Models\Company $company, ?\App\Models\Branch $branch = null): array
    {
        if (!$company) {
            return $this->getEmptyOperationalMetrics();
        }

        $cacheKey = "operational_metrics_{$company->id}_" . ($branch?->id ?? 'all');
        
        return Cache::remember($cacheKey, 300, function () use ($company, $branch) {
            // Get company phone numbers for filtering
            $phoneNumbers = $this->getCompanyPhoneNumbers($company->id);
            
            $query = Call::where('company_id', $company->id);
            if (!empty($phoneNumbers)) {
                $query->whereIn('to_number', $phoneNumbers);
            }
            if ($branch) {
                $query->where('branch_id', $branch->id);
            }

            // Active calls
            $activeCalls = $query->clone()
                ->where('status', 'in_progress')
                ->count();

            // Total calls today
            $totalCallsToday = $query->clone()
                ->whereDate('created_at', today())
                ->count();

            // Average call duration
            $avgCallDuration = $query->clone()
                ->whereNotNull('duration_seconds')
                ->avg('duration_seconds') ?? 0;

            // Appointments today
            $appointmentsQuery = Appointment::query()
                ->whereHas('staff', function ($q) use ($company) {
                    $q->where('company_id', $company->id);
                })
                ->whereDate('starts_at', today());
                
            if ($branch) {
                $appointmentsQuery->whereHas('staff', function ($q) use ($branch) {
                    $q->where('branch_id', $branch->id);
                });
            }

            $appointmentsToday = $appointmentsQuery->count();
            $completedAppointments = $appointmentsQuery->clone()
                ->where('status', 'completed')
                ->count();

            // Conversion rate
            $conversionRate = $totalCallsToday > 0 
                ? ($appointmentsToday / $totalCallsToday) * 100 
                : 0;

            // Staff utilization
            $staffQuery = \App\Models\Staff::where('company_id', $company->id)
                ->where('is_active', true);
                
            if ($branch) {
                $staffQuery->where('branch_id', $branch->id);
            }

            $totalStaff = $staffQuery->count();
            $busyStaff = $staffQuery->clone()
                ->whereHas('appointments', function ($q) {
                    $q->where('status', 'in_progress')
                        ->where('starts_at', '<=', now())
                        ->where('ends_at', '>=', now());
                })
                ->count();

            $staffUtilization = $totalStaff > 0 
                ? ($busyStaff / $totalStaff) * 100 
                : 0;

            return [
                'active_calls' => $activeCalls,
                'total_calls_today' => $totalCallsToday,
                'avg_call_duration' => round($avgCallDuration / 60, 1), // in minutes
                'appointments_today' => $appointmentsToday,
                'completed_appointments' => $completedAppointments,
                'conversion_rate' => round($conversionRate, 1),
                'staff_utilization' => round($staffUtilization, 1),
                'total_staff' => $totalStaff,
                'busy_staff' => $busyStaff,
                'no_shows_today' => $appointmentsQuery->clone()
                    ->where('status', 'no_show')
                    ->count(),
            ];
        });
    }

    /**
     * Get financial metrics
     */
    public function getFinancialMetrics(?\App\Models\Company $company, ?\App\Models\Branch $branch = null, string $period = 'today'): array
    {
        if (!$company) {
            return $this->getEmptyFinancialMetrics();
        }

        $cacheKey = "financial_metrics_{$company->id}_" . ($branch?->id ?? 'all') . "_{$period}";
        
        return Cache::remember($cacheKey, 300, function () use ($company, $branch, $period) {
            $dateRange = $this->getDateRangeByPeriod($period);
            
            // Revenue calculation
            $revenueQuery = Appointment::query()
                ->join('services', 'appointments.service_id', '=', 'services.id')
                ->whereHas('staff', function ($q) use ($company) {
                    $q->where('company_id', $company->id);
                })
                ->whereBetween('appointments.starts_at', $dateRange)
                ->where('appointments.status', 'completed');
                
            if ($branch) {
                $revenueQuery->whereHas('staff', function ($q) use ($branch) {
                    $q->where('branch_id', $branch->id);
                });
            }

            $revenue = $revenueQuery->sum('services.price') ?? 0;

            // Previous period revenue for comparison
            $previousRange = $this->getPreviousDateRangeByPeriod($period);
            $previousRevenue = $revenueQuery->clone()
                ->whereBetween('appointments.starts_at', $previousRange)
                ->sum('services.price') ?? 0;

            // Revenue change
            $revenueChange = $previousRevenue > 0 
                ? (($revenue - $previousRevenue) / $previousRevenue) * 100 
                : 0;

            // Average appointment value
            $completedCount = $revenueQuery->clone()->count();
            $avgAppointmentValue = $completedCount > 0 ? $revenue / $completedCount : 0;

            // Costs (simplified)
            $baseCost = $company->subscription_cost ?? 299;
            $callCosts = Call::where('company_id', $company->id)
                ->whereBetween('created_at', $dateRange)
                ->when($branch, fn($q) => $q->where('branch_id', $branch->id))
                ->count() * 0.10;

            $totalCosts = $baseCost + $callCosts;
            $profit = $revenue - $totalCosts;
            $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

            return [
                'revenue' => round($revenue, 2),
                'revenue_change' => round($revenueChange, 1),
                'costs' => round($totalCosts, 2),
                'profit' => round($profit, 2),
                'margin' => round($margin, 1),
                'avg_appointment_value' => round($avgAppointmentValue, 2),
                'total_appointments' => $completedCount,
                'cost_per_appointment' => $completedCount > 0 
                    ? round($totalCosts / $completedCount, 2) 
                    : 0,
            ];
        });
    }

    /**
     * Get branch comparison metrics
     */
    public function getBranchComparison(?\App\Models\Company $company, string $period = 'today'): array
    {
        if (!$company) {
            return [];
        }

        $cacheKey = "branch_comparison_{$company->id}_{$period}";
        
        return Cache::remember($cacheKey, 300, function () use ($company, $period) {
            $branches = \App\Models\Branch::where('company_id', $company->id)
                ->where('is_active', true)
                ->get();

            $comparison = [];
            
            foreach ($branches as $branch) {
                $operational = $this->getOperationalMetrics($company, $branch);
                $financial = $this->getFinancialMetrics($company, $branch, $period);
                
                $comparison[] = [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'revenue' => $financial['revenue'],
                    'appointments' => $operational['appointments_today'],
                    'conversion_rate' => $operational['conversion_rate'],
                    'staff_utilization' => $operational['staff_utilization'],
                    'avg_appointment_value' => $financial['avg_appointment_value'],
                ];
            }

            // Sort by revenue
            usort($comparison, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

            return $comparison;
        });
    }

    /**
     * Get anomalies and alerts
     */
    public function getAnomalies(?\App\Models\Company $company, ?\App\Models\Branch $branch = null): array
    {
        if (!$company) {
            return [];
        }

        $anomalies = [];

        // Check for low conversion rate
        $metrics = $this->getOperationalMetrics($company, $branch);
        if ($metrics['conversion_rate'] < 20 && $metrics['total_calls_today'] > 10) {
            $anomalies[] = [
                'type' => 'warning',
                'title' => 'Niedrige Konversionsrate',
                'message' => "Nur {$metrics['conversion_rate']}% der Anrufe führen zu Terminen",
                'metric' => 'conversion_rate',
                'value' => $metrics['conversion_rate'],
                'threshold' => 20,
            ];
        }

        // Check for high no-show rate
        if ($metrics['appointments_today'] > 0) {
            $noShowRate = ($metrics['no_shows_today'] / $metrics['appointments_today']) * 100;
            if ($noShowRate > 15) {
                $anomalies[] = [
                    'type' => 'alert',
                    'title' => 'Hohe No-Show Rate',
                    'message' => round($noShowRate, 1) . "% der Termine heute nicht wahrgenommen",
                    'metric' => 'no_show_rate',
                    'value' => round($noShowRate, 1),
                    'threshold' => 15,
                ];
            }
        }

        // Check staff utilization
        if ($metrics['staff_utilization'] < 50 && $metrics['total_staff'] > 0) {
            $anomalies[] = [
                'type' => 'info',
                'title' => 'Niedrige Auslastung',
                'message' => "Nur {$metrics['staff_utilization']}% der Mitarbeiter sind ausgelastet",
                'metric' => 'staff_utilization',
                'value' => $metrics['staff_utilization'],
                'threshold' => 50,
            ];
        }

        return $anomalies;
    }

    /**
     * Get empty operational metrics
     */
    private function getEmptyOperationalMetrics(): array
    {
        return [
            'active_calls' => 0,
            'total_calls_today' => 0,
            'avg_call_duration' => 0,
            'appointments_today' => 0,
            'completed_appointments' => 0,
            'conversion_rate' => 0,
            'staff_utilization' => 0,
            'total_staff' => 0,
            'busy_staff' => 0,
            'no_shows_today' => 0,
        ];
    }

    /**
     * Get empty financial metrics
     */
    private function getEmptyFinancialMetrics(): array
    {
        return [
            'revenue' => 0,
            'revenue_change' => 0,
            'costs' => 0,
            'profit' => 0,
            'margin' => 0,
            'avg_appointment_value' => 0,
            'total_appointments' => 0,
            'cost_per_appointment' => 0,
        ];
    }

    /**
     * Get date range based on period string
     */
    private function getDateRangeByPeriod(string $period): array
    {
        switch ($period) {
            case 'today':
                return [today()->startOfDay(), today()->endOfDay()];
            case 'week':
                return [now()->startOfWeek(), now()->endOfWeek()];
            case 'month':
                return [now()->startOfMonth(), now()->endOfMonth()];
            case 'quarter':
                return [now()->firstOfQuarter(), now()->lastOfQuarter()];
            case 'year':
                return [now()->startOfYear(), now()->endOfYear()];
            default:
                return [today()->startOfDay(), today()->endOfDay()];
        }
    }

    /**
     * Get previous date range for comparison
     */
    private function getPreviousDateRangeByPeriod(string $period): array
    {
        switch ($period) {
            case 'today':
                return [today()->subDay()->startOfDay(), today()->subDay()->endOfDay()];
            case 'week':
                return [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()];
            case 'month':
                return [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];
            case 'quarter':
                return [now()->subQuarter()->firstOfQuarter(), now()->subQuarter()->lastOfQuarter()];
            case 'year':
                return [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()];
            default:
                return [today()->subDay()->startOfDay(), today()->subDay()->endOfDay()];
        }
    }
    
    /**
     * Helper method to handle JSON queries across different databases
     * SQLite doesn't support whereJsonContains, so we need alternatives
     */
    private function applyJsonContainsQuery($query, string $column, $value)
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite workaround using json_extract and LIKE
            if (is_array($value)) {
                // For array values, check if any of them exist in the JSON
                $query->where(function($q) use ($column, $value) {
                    foreach ($value as $val) {
                        $q->orWhereRaw("json_extract({$column}, '$') LIKE ?", ['%"' . $val . '"%']);
                    }
                });
            } else {
                // For single values
                $query->whereRaw("json_extract({$column}, '$') LIKE ?", ['%"' . $value . '"%']);
            }
        } else {
            // MySQL/MariaDB native JSON support
            $query->whereJsonContains($column, $value);
        }
        
        return $query;
    }
}