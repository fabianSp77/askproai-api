<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RevenueAnalyticsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.revenue-analytics';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 3;
    
    public array $revenueData = [];
    public string $period = 'month';
    public ?int $companyId = null;
    public ?string $branchId = null;
    
    public function mount(): void
    {
        $this->companyId = session('filter_company_id') ?? auth()->user()->company_id;
        $this->branchId = session('filter_branch_id');
        $this->loadRevenueData();
    }
    
    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->loadRevenueData();
    }
    
    protected function loadRevenueData(): void
    {
        $cacheKey = "revenue_analytics_{$this->companyId}_{$this->branchId}_{$this->period}";
        
        $this->revenueData = Cache::remember($cacheKey, 300, function () {
            return [
                'summary' => $this->getRevenueSummary(),
                'breakdown' => $this->getRevenueBreakdown(),
                'trends' => $this->getRevenueTrends(),
                'top_services' => $this->getTopServices(),
                'top_staff' => $this->getTopStaffByRevenue(),
                'payment_status' => $this->getPaymentStatus(),
                'projections' => $this->getRevenueProjections(),
            ];
        });
    }
    
    protected function getRevenueSummary(): array
    {
        [$startDate, $endDate] = $this->getDateRange();
        
        // Base query for appointments
        $query = Appointment::query()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->whereBetween('appointments.starts_at', [$startDate, $endDate])
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->branchId, fn($q) => $q->where('appointments.branch_id', $this->branchId));
            
        // Total revenue (completed appointments)
        $totalRevenue = (clone $query)
            ->where('appointments.status', 'completed')
            ->sum('services.price');
            
        // Potential revenue (all scheduled/confirmed)
        $potentialRevenue = (clone $query)
            ->whereIn('appointments.status', ['scheduled', 'confirmed'])
            ->sum('services.price');
            
        // Lost revenue (cancelled/no-show)
        $lostRevenue = (clone $query)
            ->whereIn('appointments.status', ['cancelled', 'no_show'])
            ->sum('services.price');
            
        // Collection metrics
        $totalBilled = $totalRevenue; // In real implementation, this would come from invoices
        $totalCollected = $totalRevenue * 0.92; // Simulated 92% collection rate
        $outstanding = $totalBilled - $totalCollected;
        
        // Average metrics
        $completedCount = (clone $query)->where('appointments.status', 'completed')->count();
        $avgRevenuePerAppointment = $completedCount > 0 ? $totalRevenue / $completedCount : 0;
        
        // Compare with previous period
        $previousPeriod = $this->getPreviousPeriodRevenue();
        $growth = $previousPeriod > 0 
            ? round((($totalRevenue - $previousPeriod) / $previousPeriod) * 100, 1)
            : 0;
            
        return [
            'total_revenue' => $totalRevenue,
            'potential_revenue' => $potentialRevenue,
            'lost_revenue' => $lostRevenue,
            'total_billed' => $totalBilled,
            'total_collected' => $totalCollected,
            'outstanding' => $outstanding,
            'collection_rate' => $totalBilled > 0 ? round(($totalCollected / $totalBilled) * 100, 1) : 0,
            'avg_per_appointment' => round($avgRevenuePerAppointment, 2),
            'growth' => $growth,
            'growth_direction' => $growth > 0 ? 'up' : ($growth < 0 ? 'down' : 'stable'),
        ];
    }
    
    protected function getRevenueBreakdown(): array
    {
        [$startDate, $endDate] = $this->getDateRange();
        
        // By branch
        $byBranch = DB::table('appointments')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->join('branches', 'appointments.branch_id', '=', 'branches.id')
            ->whereBetween('appointments.starts_at', [$startDate, $endDate])
            ->where('appointments.status', 'completed')
            ->when($this->companyId, fn($q) => $q->where('branches.company_id', $this->companyId))
            ->when($this->branchId, fn($q) => $q->where('appointments.branch_id', $this->branchId))
            ->select(
                'branches.id',
                'branches.name',
                DB::raw('SUM(services.price) as revenue'),
                DB::raw('COUNT(appointments.id) as appointment_count')
            )
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($branch) {
                $branch->avg_per_appointment = $branch->appointment_count > 0 
                    ? round($branch->revenue / $branch->appointment_count, 2)
                    : 0;
                return $branch;
            });
            
        // By service category
        $byCategory = DB::table('appointments')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->leftJoin('service_categories', 'services.category_id', '=', 'service_categories.id')
            ->whereBetween('appointments.starts_at', [$startDate, $endDate])
            ->where('appointments.status', 'completed')
            ->when($this->companyId, function ($q) {
                $q->join('branches', 'appointments.branch_id', '=', 'branches.id')
                  ->where('branches.company_id', $this->companyId);
            })
            ->when($this->branchId, fn($q) => $q->where('appointments.branch_id', $this->branchId))
            ->select(
                DB::raw('COALESCE(service_categories.name, "Uncategorized") as category'),
                DB::raw('SUM(services.price) as revenue'),
                DB::raw('COUNT(appointments.id) as count')
            )
            ->groupBy('service_categories.name')
            ->orderByDesc('revenue')
            ->get();
            
        return [
            'by_branch' => $byBranch,
            'by_category' => $byCategory,
        ];
    }
    
    protected function getRevenueTrends(): array
    {
        $periods = $this->getPeriods();
        $trends = [];
        
        foreach ($periods as $period) {
            $revenue = Appointment::query()
                ->join('services', 'appointments.service_id', '=', 'services.id')
                ->whereBetween('appointments.starts_at', [$period['start'], $period['end']])
                ->where('appointments.status', 'completed')
                ->when($this->companyId, function ($q) {
                    $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
                })
                ->when($this->branchId, fn($q) => $q->where('appointments.branch_id', $this->branchId))
                ->sum('services.price');
                
            $appointmentCount = Appointment::query()
                ->whereBetween('starts_at', [$period['start'], $period['end']])
                ->where('status', 'completed')
                ->when($this->companyId, function ($q) {
                    $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
                })
                ->when($this->branchId, fn($q) => $q->where('branch_id', $this->branchId))
                ->count();
                
            $trends[] = [
                'label' => $period['label'],
                'revenue' => $revenue,
                'appointments' => $appointmentCount,
                'avg_value' => $appointmentCount > 0 ? round($revenue / $appointmentCount, 2) : 0,
            ];
        }
        
        return $trends;
    }
    
    protected function getTopServices(): array
    {
        [$startDate, $endDate] = $this->getDateRange();
        
        return DB::table('appointments')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->whereBetween('appointments.starts_at', [$startDate, $endDate])
            ->where('appointments.status', 'completed')
            ->when($this->companyId, function ($q) {
                $q->join('branches', 'appointments.branch_id', '=', 'branches.id')
                  ->where('branches.company_id', $this->companyId);
            })
            ->when($this->branchId, fn($q) => $q->where('appointments.branch_id', $this->branchId))
            ->select(
                'services.id',
                'services.name',
                'services.price',
                DB::raw('COUNT(appointments.id) as booking_count'),
                DB::raw('SUM(services.price) as total_revenue')
            )
            ->groupBy('services.id', 'services.name', 'services.price')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->map(function ($service) {
                $service->revenue_share = 0; // Will calculate after getting total
                return $service;
            });
    }
    
    protected function getTopStaffByRevenue(): array
    {
        [$startDate, $endDate] = $this->getDateRange();
        
        return DB::table('appointments')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->join('staff', 'appointments.staff_id', '=', 'staff.id')
            ->whereBetween('appointments.starts_at', [$startDate, $endDate])
            ->where('appointments.status', 'completed')
            ->when($this->companyId, fn($q) => $q->where('staff.company_id', $this->companyId))
            ->when($this->branchId, fn($q) => $q->where('appointments.branch_id', $this->branchId))
            ->select(
                'staff.id',
                'staff.name',
                DB::raw('COUNT(appointments.id) as appointment_count'),
                DB::raw('SUM(services.price) as revenue'),
                DB::raw('AVG(services.price) as avg_appointment_value')
            )
            ->groupBy('staff.id', 'staff.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();
    }
    
    protected function getPaymentStatus(): array
    {
        // In a real implementation, this would connect to payment/invoice data
        // For now, we'll simulate based on appointment status
        [$startDate, $endDate] = $this->getDateRange();
        
        $totalAppointments = Appointment::query()
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->branchId, fn($q) => $q->where('branch_id', $this->branchId))
            ->count();
            
        return [
            'paid' => round($totalAppointments * 0.85), // 85% paid
            'pending' => round($totalAppointments * 0.10), // 10% pending
            'overdue' => round($totalAppointments * 0.05), // 5% overdue
        ];
    }
    
    protected function getRevenueProjections(): array
    {
        // Simple projection based on current trends
        $currentMonthRevenue = $this->revenueData['summary']['total_revenue'] ?? 0;
        $growthRate = $this->revenueData['summary']['growth'] ?? 0;
        
        $projections = [];
        $baseRevenue = $currentMonthRevenue;
        
        for ($i = 1; $i <= 3; $i++) {
            $projectedRevenue = $baseRevenue * (1 + ($growthRate / 100));
            $projections[] = [
                'month' => Carbon::now()->addMonths($i)->format('F Y'),
                'projected_revenue' => round($projectedRevenue, 2),
                'confidence' => max(50, 95 - ($i * 15)), // Confidence decreases with time
            ];
            $baseRevenue = $projectedRevenue;
        }
        
        return $projections;
    }
    
    protected function getDateRange(): array
    {
        $endDate = Carbon::now()->endOfDay();
        
        switch ($this->period) {
            case 'week':
                $startDate = Carbon::now()->startOfWeek();
                break;
            case 'month':
                $startDate = Carbon::now()->startOfMonth();
                break;
            case 'quarter':
                $startDate = Carbon::now()->startOfQuarter();
                break;
            case 'year':
                $startDate = Carbon::now()->startOfYear();
                break;
            default:
                $startDate = Carbon::now()->startOfMonth();
        }
        
        return [$startDate, $endDate];
    }
    
    protected function getPeriods(): array
    {
        $periods = [];
        
        switch ($this->period) {
            case 'week':
                // Daily for the week
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $periods[] = [
                        'start' => $date->copy()->startOfDay(),
                        'end' => $date->copy()->endOfDay(),
                        'label' => $date->format('D'),
                    ];
                }
                break;
            case 'month':
                // Weekly for the month
                for ($i = 3; $i >= 0; $i--) {
                    $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
                    $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();
                    $periods[] = [
                        'start' => $weekStart,
                        'end' => $weekEnd,
                        'label' => 'W' . $weekStart->weekOfYear,
                    ];
                }
                break;
            case 'quarter':
                // Monthly for the quarter
                for ($i = 2; $i >= 0; $i--) {
                    $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
                    $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
                    $periods[] = [
                        'start' => $monthStart,
                        'end' => $monthEnd,
                        'label' => $monthStart->format('M'),
                    ];
                }
                break;
            case 'year':
                // Monthly for the year
                for ($i = 11; $i >= 0; $i--) {
                    $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
                    $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
                    $periods[] = [
                        'start' => $monthStart,
                        'end' => $monthEnd,
                        'label' => $monthStart->format('M'),
                    ];
                }
                break;
        }
        
        return $periods;
    }
    
    protected function getPreviousPeriodRevenue(): float
    {
        [$currentStart, $currentEnd] = $this->getDateRange();
        $periodLength = $currentStart->diffInDays($currentEnd);
        
        $previousStart = $currentStart->copy()->subDays($periodLength + 1);
        $previousEnd = $currentStart->copy()->subDay();
        
        return Appointment::query()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->whereBetween('appointments.starts_at', [$previousStart, $previousEnd])
            ->where('appointments.status', 'completed')
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->branchId, fn($q) => $q->where('appointments.branch_id', $this->branchId))
            ->sum('services.price');
    }
    
    public function getPeriodOptions(): array
    {
        return [
            'week' => 'This Week',
            'month' => 'This Month',
            'quarter' => 'This Quarter',
            'year' => 'This Year',
        ];
    }
    
    public static function canView(): bool
    {
        return true;
    }
}