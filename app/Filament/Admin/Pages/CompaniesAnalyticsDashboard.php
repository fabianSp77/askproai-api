<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Company;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\PrepaidBalance;
use App\Models\BalanceTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CompaniesAnalyticsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Firmen-Analyse';
    protected static ?string $navigationGroup = '📊 Dashboards';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.admin.pages.companies-analytics-professional';

    public array $overviewStats = [];
    public array $companiesPerformance = [];
    public array $revenueData = [];
    public array $appointmentsData = [];
    public array $callVolumeData = [];
    public array $topPerformers = [];
    public array $activityTimeline = [];
    public array $comparisonMetrics = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        return $user->hasRole(['Super Admin', 'Admin']);
    }

    public function mount(): void
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData(): void
    {
        $this->loadOverviewStats();
        $this->loadCompaniesPerformance();
        $this->loadRevenueData();
        $this->loadAppointmentsData();
        $this->loadCallVolumeData();
        $this->loadTopPerformers();
        $this->loadActivityTimeline();
        $this->loadComparisonMetrics();
    }

    protected function loadOverviewStats(): void
    {
        $totalCompanies = Company::count();
        $activeCompanies = Company::whereIn('subscription_status', ['active', 'trial'])->count();
        $totalRevenue = BalanceTransaction::where('type', 'debit')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');
        $totalCalls = Call::where('created_at', '>=', now()->startOfMonth())->count();
        $totalAppointments = Appointment::where('created_at', '>=', now()->startOfMonth())->count();
        $avgCallDuration = Call::where('created_at', '>=', now()->startOfMonth())
            ->whereNotNull('duration_sec')
            ->avg('duration_sec');

        // Growth calculations
        $lastMonthRevenue = BalanceTransaction::where('type', 'debit')
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('amount');
        $revenueGrowth = $lastMonthRevenue > 0 ? (($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        $lastMonthCalls = Call::whereBetween('created_at', [
            now()->subMonth()->startOfMonth(), 
            now()->subMonth()->endOfMonth()
        ])->count();
        $callsGrowth = $lastMonthCalls > 0 ? (($totalCalls - $lastMonthCalls) / $lastMonthCalls) * 100 : 0;

        $this->overviewStats = [
            'total_companies' => $totalCompanies,
            'active_companies' => $activeCompanies,
            'total_revenue' => $totalRevenue,
            'total_calls' => $totalCalls,
            'total_appointments' => $totalAppointments,
            'avg_call_duration' => round($avgCallDuration / 60, 1), // in minutes
            'revenue_growth' => round($revenueGrowth, 1),
            'calls_growth' => round($callsGrowth, 1),
            'conversion_rate' => $totalCalls > 0 ? round(($totalAppointments / $totalCalls) * 100, 1) : 0,
        ];
    }

    protected function loadCompaniesPerformance(): void
    {
        $companies = Company::select('id', 'name')
            ->withCount(['calls' => function ($query) {
                $query->where('created_at', '>=', now()->startOfMonth());
            }])
            ->withCount(['appointments' => function ($query) {
                $query->where('created_at', '>=', now()->startOfMonth());
            }])
            ->with(['prepaidBalance'])
            ->orderBy('calls_count', 'desc')
            ->limit(10)
            ->get();

        $this->companiesPerformance = $companies->map(function ($company) {
            $revenue = BalanceTransaction::where('company_id', $company->id)
                ->where('type', 'debit')
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('amount');

            return [
                'name' => $company->name,
                'calls' => $company->calls_count,
                'appointments' => $company->appointments_count,
                'revenue' => $revenue,
                'balance' => $company->prepaidBalance?->effective_balance ?? 0,
                'conversion_rate' => $company->calls_count > 0 ? 
                    round(($company->appointments_count / $company->calls_count) * 100, 1) : 0,
            ];
        })->toArray();
    }

    protected function loadRevenueData(): void
    {
        $data = [];
        $labels = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $revenue = BalanceTransaction::where('type', 'debit')
                ->whereDate('created_at', $date)
                ->sum('amount');
            
            $data[] = round($revenue, 2);
            $labels[] = $date->format('M d');
        }

        $this->revenueData = [
            'labels' => $labels,
            'data' => $data,
            'total_week' => array_sum($data),
        ];
    }

    protected function loadAppointmentsData(): void
    {
        $companies = Company::select('name')
            ->withCount(['appointments' => function ($query) {
                $query->where('created_at', '>=', now()->startOfMonth());
            }])
            ->orderBy('appointments_count', 'desc')
            ->limit(6)
            ->get();

        $this->appointmentsData = [
            'labels' => $companies->pluck('name')->toArray(),
            'data' => $companies->pluck('appointments_count')->toArray(),
            'colors' => [
                'rgba(59, 130, 246, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(239, 68, 68, 0.8)',
                'rgba(139, 92, 246, 0.8)',
                'rgba(236, 72, 153, 0.8)',
            ],
        ];
    }

    protected function loadCallVolumeData(): void
    {
        $heatmapData = [];
        $hours = range(0, 23);
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        
        foreach ($days as $dayIndex => $day) {
            $dayData = [];
            foreach ($hours as $hour) {
                $calls = Call::where('created_at', '>=', now()->startOfWeek())
                    ->where('created_at', '<=', now()->endOfWeek())
                    ->whereRaw('DAYOFWEEK(created_at) = ?', [$dayIndex + 2]) // MySQL DAYOFWEEK starts from Sunday = 1
                    ->whereRaw('HOUR(created_at) = ?', [$hour])
                    ->count();
                
                $dayData[] = $calls;
            }
            $heatmapData[] = [
                'day' => $day,
                'data' => $dayData,
            ];
        }

        $this->callVolumeData = [
            'heatmap' => $heatmapData,
            'hours' => $hours,
            'peak_hour' => $this->findPeakHour($heatmapData),
        ];
    }

    protected function findPeakHour(array $heatmapData): int
    {
        $hourTotals = array_fill(0, 24, 0);
        
        foreach ($heatmapData as $dayData) {
            foreach ($dayData['data'] as $hour => $calls) {
                $hourTotals[$hour] += $calls;
            }
        }
        
        return array_search(max($hourTotals), $hourTotals);
    }

    protected function loadTopPerformers(): void
    {
        $this->topPerformers = [
            'revenue' => Company::select('companies.name')
                ->join('balance_transactions', 'companies.id', '=', 'balance_transactions.company_id')
                ->where('balance_transactions.type', 'debit')
                ->where('balance_transactions.created_at', '>=', now()->startOfMonth())
                ->groupBy('companies.id', 'companies.name')
                ->orderByRaw('SUM(balance_transactions.amount) DESC')
                ->limit(3)
                ->get()
                ->pluck('name')
                ->toArray(),
                
            'calls' => Company::withCount(['calls' => function ($query) {
                $query->where('created_at', '>=', now()->startOfMonth());
            }])
                ->orderBy('calls_count', 'desc')
                ->limit(3)
                ->get()
                ->pluck('name')
                ->toArray(),
                
            'conversion' => Company::select('companies.name')
                ->selectRaw('
                    COUNT(CASE WHEN appointments.id IS NOT NULL THEN 1 END) as appointments_count,
                    COUNT(calls.id) as calls_count,
                    CASE 
                        WHEN COUNT(calls.id) > 0 THEN (COUNT(CASE WHEN appointments.id IS NOT NULL THEN 1 END) / COUNT(calls.id)) * 100
                        ELSE 0 
                    END as conversion_rate
                ')
                ->leftJoin('calls', 'companies.id', '=', 'calls.company_id')
                ->leftJoin('appointments', function ($join) {
                    $join->on('calls.id', '=', 'appointments.call_id')
                         ->where('appointments.created_at', '>=', now()->startOfMonth());
                })
                ->where('calls.created_at', '>=', now()->startOfMonth())
                ->groupBy('companies.id', 'companies.name')
                ->having('calls_count', '>', 5) // Only companies with meaningful call volume
                ->orderBy('conversion_rate', 'desc')
                ->limit(3)
                ->get()
                ->pluck('name')
                ->toArray(),
        ];
    }

    protected function loadActivityTimeline(): void
    {
        $this->activityTimeline = [
            ['time' => '2 min ago', 'event' => 'New appointment booked', 'company' => 'TechCorp', 'type' => 'success'],
            ['time' => '5 min ago', 'event' => 'Call completed', 'company' => 'MedCenter', 'type' => 'info'],
            ['time' => '8 min ago', 'event' => 'Balance topped up', 'company' => 'LegalFirm', 'type' => 'success'],
            ['time' => '12 min ago', 'event' => 'Failed call attempt', 'company' => 'AutoDealer', 'type' => 'warning'],
            ['time' => '15 min ago', 'event' => 'New company registered', 'company' => 'StartupXYZ', 'type' => 'success'],
        ];
    }

    protected function loadComparisonMetrics(): void
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        
        $currentRevenue = BalanceTransaction::where('type', 'debit')
            ->where('created_at', '>=', $currentMonth)
            ->sum('amount');
            
        $lastMonthRevenue = BalanceTransaction::where('type', 'debit')
            ->whereBetween('created_at', [$lastMonth, $lastMonth->copy()->endOfMonth()])
            ->sum('amount');

        $currentCalls = Call::where('created_at', '>=', $currentMonth)->count();
        $lastMonthCalls = Call::whereBetween('created_at', [$lastMonth, $lastMonth->copy()->endOfMonth()])->count();

        $currentAppointments = Appointment::where('created_at', '>=', $currentMonth)->count();
        $lastMonthAppointments = Appointment::whereBetween('created_at', [$lastMonth, $lastMonth->copy()->endOfMonth()])->count();

        $this->comparisonMetrics = [
            'revenue' => [
                'current' => $currentRevenue,
                'previous' => $lastMonthRevenue,
                'change' => $lastMonthRevenue > 0 ? round((($currentRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1) : 0,
            ],
            'calls' => [
                'current' => $currentCalls,
                'previous' => $lastMonthCalls,
                'change' => $lastMonthCalls > 0 ? round((($currentCalls - $lastMonthCalls) / $lastMonthCalls) * 100, 1) : 0,
            ],
            'appointments' => [
                'current' => $currentAppointments,
                'previous' => $lastMonthAppointments,
                'change' => $lastMonthAppointments > 0 ? round((($currentAppointments - $lastMonthAppointments) / $lastMonthAppointments) * 100, 1) : 0,
            ],
        ];
    }

    public function refresh(): void
    {
        $this->loadDashboardData();
        $this->dispatch('dashboard-refreshed');
    }
}