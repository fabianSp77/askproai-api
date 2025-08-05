<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SystemStatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        // Today's appointments
        $todayAppointments = Appointment::whereDate('starts_at', $today)->count();
        $yesterdayAppointments = Appointment::whereDate('starts_at', $today->copy()->subDay())->count();
        $appointmentChange = $yesterdayAppointments > 0 
            ? round((($todayAppointments - $yesterdayAppointments) / $yesterdayAppointments) * 100)
            : 0;
        
        // Today's calls
        $todayCalls = Call::whereDate('created_at', $today)->count();
        $yesterdayCalls = Call::whereDate('created_at', $today->copy()->subDay())->count();
        $callChange = $yesterdayCalls > 0
            ? round((($todayCalls - $yesterdayCalls) / $yesterdayCalls) * 100)
            : 0;
        
        // Active companies
        $activeCompanies = Company::whereHas('branches', function($query) {
            $query->where('is_active', true);
        })->count();
        
        // Total revenue this month (placeholder)
        $monthlyRevenue = DB::table('calls')
            ->where('created_at', '>=', $thisMonth)
            ->sum('cost');
        
        return [
            Stat::make("Today's Appointments", $todayAppointments)
                ->description($appointmentChange >= 0 ? "{$appointmentChange}% increase" : "{$appointmentChange}% decrease")
                ->descriptionIcon($appointmentChange >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($appointmentChange >= 0 ? 'success' : 'warning')
                ->icon('heroicon-o-calendar-days'),
                
            Stat::make("Today's Calls", $todayCalls)
                ->description($callChange >= 0 ? "{$callChange}% increase" : "{$callChange}% decrease")
                ->descriptionIcon($callChange >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($callChange >= 0 ? 'success' : 'warning')
                ->icon('heroicon-o-phone'),
                
            Stat::make('Active Companies', $activeCompanies)
                ->description(Branch::where('active', true)->count() . ' active branches')
                ->icon('heroicon-o-building-office-2')
                ->color('primary'),
                
            Stat::make('Monthly Costs', '€' . number_format($monthlyRevenue, 2))
                ->description('Call costs this month')
                ->icon('heroicon-o-currency-euro')
                ->color('info'),
        ];
    }
    
    public function getPollingInterval(): ?string
    {
        return '60s';
    }
}