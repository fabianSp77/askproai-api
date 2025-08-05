<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;

class UnifiedDashboardWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '30s';
    protected static bool $isLazy = true;
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        return Cache::remember('unified-dashboard-stats-' . auth()->user()->company_id, 30, function () {
            $company_id = auth()->user()->company_id;
            
            // Calls metrics
            $todayCalls = Call::where('company_id', $company_id)
                ->whereDate('created_at', today())
                ->count();
                
            $callsGrowth = $this->calculateGrowth('calls', $todayCalls);
            
            // Appointments metrics
            $todayAppointments = Appointment::where('company_id', $company_id)
                ->whereDate('starts_at', today())
                ->count();
                
            $appointmentsGrowth = $this->calculateGrowth('appointments', $todayAppointments);
            
            // Customers metrics
            $newCustomers = Customer::where('company_id', $company_id)
                ->whereDate('created_at', today())
                ->count();
                
            $customersGrowth = $this->calculateGrowth('customers', $newCustomers);
            
            // Revenue (simplified)
            $todayRevenue = Appointment::where('company_id', $company_id)
                ->whereDate('starts_at', today())
                ->where('status', 'completed')
                ->count() * 50; // Simplified calculation
            
            return [
                Stat::make('Calls Today', $todayCalls)
                    ->description($callsGrowth > 0 ? "+{$callsGrowth}%" : "{$callsGrowth}%")
                    ->descriptionIcon($callsGrowth > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($callsGrowth > 0 ? 'success' : 'danger'),
                    
                Stat::make('Appointments', $todayAppointments)
                    ->description($appointmentsGrowth > 0 ? "+{$appointmentsGrowth}%" : "{$appointmentsGrowth}%")
                    ->descriptionIcon($appointmentsGrowth > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($appointmentsGrowth > 0 ? 'success' : 'danger'),
                    
                Stat::make('New Customers', $newCustomers)
                    ->description($customersGrowth > 0 ? "+{$customersGrowth}%" : "{$customersGrowth}%")
                    ->descriptionIcon($customersGrowth > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($customersGrowth > 0 ? 'success' : 'danger'),
                    
                Stat::make('Revenue', '€' . number_format($todayRevenue, 2))
                    ->description('Today\'s earnings')
                    ->descriptionIcon('heroicon-m-currency-euro')
                    ->color('success'),
            ];
        });
    }
    
    private function calculateGrowth(string $metric, int $current): int
    {
        $yesterday = Cache::remember("yesterday-{$metric}-" . auth()->user()->company_id, 3600, function () use ($metric) {
            $company_id = auth()->user()->company_id;
            
            return match($metric) {
                'calls' => Call::where('company_id', $company_id)
                    ->whereDate('created_at', today()->subDay())
                    ->count(),
                'appointments' => Appointment::where('company_id', $company_id)
                    ->whereDate('starts_at', today()->subDay())
                    ->count(),
                'customers' => Customer::where('company_id', $company_id)
                    ->whereDate('created_at', today()->subDay())
                    ->count(),
                default => 0
            };
        });
        
        if ($yesterday == 0) return 0;
        
        return round((($current - $yesterday) / $yesterday) * 100);
    }
}
