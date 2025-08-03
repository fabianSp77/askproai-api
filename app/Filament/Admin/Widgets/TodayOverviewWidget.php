<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Number;

class TodayOverviewWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.today-overview-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1;
    
    public $appointmentsToday = 0;
    public $callsToday = 0;
    public $newCustomersToday = 0;
    public $revenueToday = 0;
    public $appointmentsTrend = 0;
    public $callsTrend = 0;
    public $customersTrend = 0;
    public $revenueTrend = 0;
    
    // Enable real-time updates
    protected static ?string $pollingInterval = '60s';
    
    public function mount(): void
    {
        $this->loadData();
    }
    
    public function loadData(): void
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        
        // Today's data
        $this->appointmentsToday = Appointment::whereDate('starts_at', $today)->count();
        $this->callsToday = Call::whereDate('created_at', $today)->count();
        $this->newCustomersToday = Customer::whereDate('created_at', $today)->count();
        
        // Calculate revenue (assuming appointments have a price field or related service)
        $this->revenueToday = Appointment::whereDate('starts_at', $today)
            ->whereHas('service')
            ->with('service')
            ->get()
            ->sum(function ($appointment) {
                return $appointment->service->price ?? 0;
            });
        
        // Yesterday's data for trends
        $appointmentsYesterday = Appointment::whereDate('starts_at', $yesterday)->count();
        $callsYesterday = Call::whereDate('created_at', $yesterday)->count();
        $customersYesterday = Customer::whereDate('created_at', $yesterday)->count();
        $revenueYesterday = Appointment::whereDate('starts_at', $yesterday)
            ->whereHas('service')
            ->with('service')
            ->get()
            ->sum(function ($appointment) {
                return $appointment->service->price ?? 0;
            });
        
        // Calculate trends
        $this->appointmentsTrend = $this->calculateTrend($this->appointmentsToday, $appointmentsYesterday);
        $this->callsTrend = $this->calculateTrend($this->callsToday, $callsYesterday);
        $this->customersTrend = $this->calculateTrend($this->newCustomersToday, $customersYesterday);
        $this->revenueTrend = $this->calculateTrend($this->revenueToday, $revenueYesterday);
    }
    
    private function calculateTrend($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
    
    // Livewire lifecycle hook for polling
    public function poll(): void
    {
        $this->loadData();
    }
}