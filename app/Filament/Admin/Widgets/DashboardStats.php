<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Company;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStats extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected static ?int $sort = 0;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static bool $isLazy = false;
    
    protected function getStats(): array
    {
        // Temporarily disable cache for debugging
        // return Cache::remember('dashboard_stats_' . auth()->id(), 300, function () {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            $lastWeek = Carbon::now()->subWeek();
            $lastMonth = Carbon::now()->subMonth();
            
            // Total customers
            $totalCustomers = Customer::count();
            $newCustomersToday = Customer::whereDate('created_at', $today)->count();
            $newCustomersYesterday = Customer::whereDate('created_at', $yesterday)->count();
            $customersTrend = $this->calculateTrend($newCustomersToday, $newCustomersYesterday);
            
            // Debug logging
            \Log::info('DashboardStats Debug', [
                'user_id' => auth()->id(),
                'total_customers' => $totalCustomers,
                'customers_query' => Customer::toSql(),
                'customers_without_scopes' => Customer::withoutGlobalScopes()->count(),
            ]);
            
            // Active companies/tenants
            $activeCompanies = Company::whereHas('appointments', function ($query) use ($lastMonth) {
                $query->where('created_at', '>=', $lastMonth);
            })->count();
            
            // Appointments
            $appointmentsToday = Appointment::whereDate('starts_at', $today)->count();
            $appointmentsYesterday = Appointment::whereDate('starts_at', $yesterday)->count();
            $appointmentsTrend = $this->calculateTrend($appointmentsToday, $appointmentsYesterday);
            
            // Calls
            $callsToday = Call::whereDate('created_at', $today)->count();
            $callsYesterday = Call::whereDate('created_at', $yesterday)->count();
            $callsTrend = $this->calculateTrend($callsToday, $callsYesterday);
            
            // Conversion rate
            $callsWithAppointment = Call::whereDate('created_at', $today)
                ->where(function($query) {
                    $query->whereNotNull('appointment_id')
                          ->orWhereHas('appointmentViaCallId');
                })
                ->count();
            $conversionRate = $callsToday > 0 ? round(($callsWithAppointment / $callsToday) * 100, 1) : 0;
            
            return [
                Stat::make('Gesamtkunden', number_format($totalCustomers, 0, ',', '.'))
                    ->description($newCustomersToday . ' neu heute')
                    ->descriptionIcon($customersTrend > 0 ? 'heroicon-m-arrow-trending-up' : ($customersTrend < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus'))
                    ->chart($this->getCustomerChart())
                    ->color($customersTrend > 0 ? 'success' : ($customersTrend < 0 ? 'danger' : 'gray')),
                    
                Stat::make('Termine heute', $appointmentsToday)
                    ->description($appointmentsTrend > 0 ? '+' . $appointmentsTrend . '%' : $appointmentsTrend . '%')
                    ->descriptionIcon($appointmentsTrend > 0 ? 'heroicon-m-arrow-trending-up' : ($appointmentsTrend < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus'))
                    ->chart($this->getAppointmentChart())
                    ->color($appointmentsTrend > 0 ? 'success' : ($appointmentsTrend < 0 ? 'danger' : 'gray')),
                    
                Stat::make('Anrufe heute', $callsToday)
                    ->description('Konversion: ' . $conversionRate . '%')
                    ->descriptionIcon('heroicon-m-phone')
                    ->chart($this->getCallChart())
                    ->color($conversionRate > 50 ? 'success' : ($conversionRate > 25 ? 'warning' : 'danger')),
                    
                Stat::make('Aktive Firmen', $activeCompanies)
                    ->description('In den letzten 30 Tagen')
                    ->descriptionIcon('heroicon-m-building-office')
                    ->color('primary'),
            ];
        // });
    }
    
    private function calculateTrend($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
    
    private function getCustomerChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = Customer::whereDate('created_at', Carbon::today()->subDays($i))->count();
        }
        return $data;
    }
    
    private function getAppointmentChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = Appointment::whereDate('starts_at', Carbon::today()->subDays($i))->count();
        }
        return $data;
    }
    
    private function getCallChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = Call::whereDate('created_at', Carbon::today()->subDays($i))->count();
        }
        return $data;
    }
}
