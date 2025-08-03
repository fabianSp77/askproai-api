<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Branch;
use App\Models\Appointment;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BranchPerformanceWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.branch-performance';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;
    
    public function getPerformanceData(): array
    {
        return [
            'overview' => $this->getOverviewMetrics(),
            'topBranches' => $this->getTopPerformingBranches(),
            'utilization' => $this->getUtilizationMetrics(),
            'comparison' => $this->getBranchComparison(),
        ];
    }
    
    private function getOverviewMetrics(): array
    {
        $totalBranches = Branch::count();
        $activeBranches = Branch::where('active', true)->count();
        
        return [
            'total_branches' => $totalBranches,
            'active_branches' => $activeBranches,
            'avg_staff_per_branch' => Branch::has('staff')->avg(
                DB::raw('(SELECT COUNT(*) FROM staff WHERE home_branch_id = branches.id)')
            ) ?? 0,
            'avg_services_per_branch' => Branch::has('services')->avg(
                DB::raw('(SELECT COUNT(*) FROM services WHERE branch_id = branches.id)')
            ) ?? 0,
            'total_appointments_today' => Appointment::whereIn('branch_id', Branch::pluck('id'))
                ->whereDate('starts_at', Carbon::today())
                ->count(),
            'total_revenue_this_month' => $this->calculateTotalRevenue(),
        ];
    }
    
    private function getTopPerformingBranches(): array
    {
        return Branch::select('branches.id', 'branches.name', 'branches.company_id')
            ->selectRaw('COUNT(DISTINCT appointments.id) as appointment_count')
            ->selectRaw('COUNT(DISTINCT appointments.customer_id) as unique_customers')
            ->selectRaw('AVG(CASE WHEN appointments.status = "completed" THEN 1 ELSE 0 END) * 100 as completion_rate')
            ->leftJoin('appointments', 'branches.id', '=', 'appointments.branch_id')
            ->where('appointments.created_at', '>=', Carbon::now()->startOfMonth())
            ->groupBy('branches.id', 'branches.name', 'branches.company_id')
            ->orderByDesc('appointment_count')
            ->limit(5)
            ->get()
            ->map(function ($branch) {
                // Load company relationship
                $branch->load('company');
                
                $revenue = Appointment::where('appointments.branch_id', $branch->id)
                    ->join('services', 'appointments.service_id', '=', 'services.id')
                    ->where('appointments.status', 'completed')
                    ->where('appointments.created_at', '>=', Carbon::now()->startOfMonth())
                    ->sum('services.price');
                    
                $staffCount = $branch->staff()->count();
                $serviceCount = $branch->services()->count();
                
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'company' => $branch->company->name,
                    'appointments' => $branch->appointment_count,
                    'unique_customers' => $branch->unique_customers,
                    'completion_rate' => round($branch->completion_rate, 1),
                    'revenue' => $revenue,
                    'staff_count' => $staffCount,
                    'service_count' => $serviceCount,
                    'performance_score' => $this->calculatePerformanceScore($branch),
                ];
            })
            ->toArray();
    }
    
    private function getUtilizationMetrics(): array
    {
        $branches = Branch::with(['staff', 'services'])->get();
        
        $utilization = [];
        foreach ($branches as $branch) {
            $totalSlots = $this->calculateTotalSlots($branch);
            $bookedSlots = $this->calculateBookedSlots($branch);
            $utilizationRate = $totalSlots > 0 ? ($bookedSlots / $totalSlots) * 100 : 0;
            
            $utilization[] = [
                'branch' => $branch->name,
                'total_slots' => $totalSlots,
                'booked_slots' => $bookedSlots,
                'available_slots' => $totalSlots - $bookedSlots,
                'utilization_rate' => round($utilizationRate, 1),
                'color' => $this->getUtilizationColor($utilizationRate),
            ];
        }
        
        return collect($utilization)
            ->sortByDesc('utilization_rate')
            ->take(10)
            ->values()
            ->toArray();
    }
    
    private function getBranchComparison(): array
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        return Branch::with('company')
            ->get()
            ->map(function ($branch) use ($thisMonth, $lastMonth) {
                $appointmentsThisMonth = Appointment::where('branch_id', $branch->id)
                    ->where('created_at', '>=', $thisMonth)
                    ->count();
                    
                $appointmentsLastMonth = Appointment::where('branch_id', $branch->id)
                    ->whereBetween('created_at', [$lastMonth, $thisMonth])
                    ->count();
                    
                $growth = $appointmentsLastMonth > 0 
                    ? round((($appointmentsThisMonth - $appointmentsLastMonth) / $appointmentsLastMonth) * 100, 1)
                    : 0;
                    
                return [
                    'branch' => $branch->name,
                    'this_month' => $appointmentsThisMonth,
                    'last_month' => $appointmentsLastMonth,
                    'growth' => $growth,
                    'trend' => $growth > 0 ? 'up' : ($growth < 0 ? 'down' : 'stable'),
                ];
            })
            ->sortByDesc('growth')
            ->take(10)
            ->values()
            ->toArray();
    }
    
    private function calculateTotalRevenue(): float
    {
        return Appointment::whereIn('appointments.branch_id', Branch::pluck('id'))
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->where('appointments.status', 'completed')
            ->where('appointments.created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('services.price');
    }
    
    private function calculatePerformanceScore($branch): int
    {
        $score = 0;
        
        // Appointment count (30 points max)
        $appointmentScore = min(30, ($branch->appointment_count / 100) * 30);
        $score += $appointmentScore;
        
        // Completion rate (30 points max)
        $completionScore = ($branch->completion_rate / 100) * 30;
        $score += $completionScore;
        
        // Unique customers (20 points max)
        $customerScore = min(20, ($branch->unique_customers / 50) * 20);
        $score += $customerScore;
        
        // Staff utilization (20 points max)
        $staffCount = $branch->staff()->count();
        if ($staffCount > 0) {
            $avgAppointmentsPerStaff = $branch->appointment_count / $staffCount;
            $utilizationScore = min(20, ($avgAppointmentsPerStaff / 20) * 20);
            $score += $utilizationScore;
        }
        
        return round($score);
    }
    
    private function calculateTotalSlots($branch): int
    {
        // This is a simplified calculation
        // In reality, you'd calculate based on working hours and service durations
        $workingDays = Carbon::now()->daysInMonth - 8; // Assume 8 weekend days
        $hoursPerDay = 8;
        $slotsPerHour = 2; // 30-minute slots
        $staffCount = $branch->staff()->count();
        
        return $workingDays * $hoursPerDay * $slotsPerHour * $staffCount;
    }
    
    private function calculateBookedSlots($branch): int
    {
        return Appointment::where('branch_id', $branch->id)
            ->whereMonth('starts_at', Carbon::now()->month)
            ->count();
    }
    
    private function getUtilizationColor($rate): string
    {
        if ($rate >= 80) return 'red';
        if ($rate >= 60) return 'yellow';
        if ($rate >= 40) return 'green';
        return 'gray';
    }
}