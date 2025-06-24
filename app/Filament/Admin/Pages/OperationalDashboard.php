<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use App\Services\Dashboard\DashboardMetricsService;
use App\Filament\Admin\Traits\HasConsistentNavigation;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class OperationalDashboard extends Page
{
    use HasConsistentNavigation;
    
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $slug = 'dashboard';
    protected static string $view = 'filament.admin.pages.operational-dashboard';
    
    protected DashboardMetricsService $metricsService;
    
    // Dashboard Data Properties
    public $todayAppointments = 0;
    public $appointmentsTrend = 0;
    public $activeCalls = 0;
    public $totalCallsToday = 0;
    public $conversionRate = 0;
    public $revenueToday = 0;
    public $appointmentsCompleted = 0;
    
    // Chart Data
    public $scheduledCount = 0;
    public $scheduledPercentage = 0;
    public $completedCount = 0;
    public $completedPercentage = 0;
    public $cancelledCount = 0;
    public $cancelledPercentage = 0;
    public $noShowCount = 0;
    public $noShowPercentage = 0;
    
    // Recent Activities
    public $recentActivities = [];
    
    public function boot(DashboardMetricsService $metricsService): void
    {
        $this->metricsService = $metricsService;
    }
    
    public function mount(): void
    {
        $this->loadDashboardData();
    }
    
    protected function loadDashboardData(): void
    {
        // Today's appointments
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        
        $this->todayAppointments = Appointment::whereDate('starts_at', $today)->count();
        $yesterdayAppointments = Appointment::whereDate('starts_at', $yesterday)->count();
        
        // Calculate trend
        if ($yesterdayAppointments > 0) {
            $this->appointmentsTrend = round((($this->todayAppointments - $yesterdayAppointments) / $yesterdayAppointments) * 100);
        }
        
        // Active calls (simulated for now)
        $this->activeCalls = Call::where('status', 'in_progress')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->count();
            
        $this->totalCallsToday = Call::whereDate('created_at', $today)->count();
        
        // Conversion rate
        $callsWithAppointments = Call::whereDate('created_at', $today)
            ->whereHas('appointment')
            ->count();
            
        if ($this->totalCallsToday > 0) {
            $this->conversionRate = round(($callsWithAppointments / $this->totalCallsToday) * 100);
        }
        
        // Revenue today
        $this->appointmentsCompleted = Appointment::whereDate('starts_at', $today)
            ->where('status', 'completed')
            ->count();
            
        $this->revenueToday = Appointment::whereDate('starts_at', $today)
            ->where('status', 'completed')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->sum('services.price');
            
        // Appointment status distribution
        $statusCounts = Appointment::whereDate('starts_at', '>=', now()->subDays(7))
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        $totalAppointments = array_sum($statusCounts);
        
        $this->scheduledCount = $statusCounts['scheduled'] ?? 0;
        $this->completedCount = $statusCounts['completed'] ?? 0;
        $this->cancelledCount = $statusCounts['cancelled'] ?? 0;
        $this->noShowCount = $statusCounts['no_show'] ?? 0;
        
        if ($totalAppointments > 0) {
            $this->scheduledPercentage = round(($this->scheduledCount / $totalAppointments) * 100);
            $this->completedPercentage = round(($this->completedCount / $totalAppointments) * 100);
            $this->cancelledPercentage = round(($this->cancelledCount / $totalAppointments) * 100);
            $this->noShowPercentage = round(($this->noShowCount / $totalAppointments) * 100);
        }
        
        // Recent activities (simplified for now)
        $this->recentActivities = collect([]);
    }
    
    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['super_admin', 'company_admin', 'branch_manager']);
    }
    
    #[On('refresh-dashboard')]
    public function refresh(): void
    {
        $this->loadDashboardData();
        $this->dispatch('$refresh');
    }
    
    public function getPollingInterval(): ?string
    {
        return '30s'; // Auto-refresh every 30 seconds
    }
}