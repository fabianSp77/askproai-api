<?php

namespace App\Filament\Business\Widgets;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // Get stats based on user permissions
        $callsQuery = Call::where('company_id', $companyId);
        $appointmentsQuery = Appointment::where('company_id', $companyId);
        $customersQuery = Customer::where('company_id', $companyId);
        
        // Apply team/own filters based on role
        if ($user->hasRole('company_staff')) {
            // Staff can only see their own data
            $callsQuery->where('assigned_to', $user->id);
            $appointmentsQuery->where('staff_id', $user->id);
        } elseif ($user->hasRole('company_manager')) {
            // Managers see team data
            $teamIds = $user->teamMembers()->pluck('id')->push($user->id);
            $callsQuery->whereIn('assigned_to', $teamIds);
            $appointmentsQuery->whereIn('staff_id', $teamIds);
        }
        // company_owner and company_admin see all company data (no additional filters)
        
        $todayCallsCount = (clone $callsQuery)->whereDate('created_at', today())->count();
        $monthCallsCount = (clone $callsQuery)->whereMonth('created_at', now()->month)->count();
        
        $upcomingAppointments = (clone $appointmentsQuery)
            ->where('start_time', '>=', now())
            ->count();
            
        $activeCustomers = $customersQuery->count();
        
        return [
            Stat::make('Today\'s Calls', $todayCallsCount)
                ->description('Total calls today')
                ->descriptionIcon('heroicon-m-phone')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),
                
            Stat::make('Month Calls', $monthCallsCount)
                ->description('Calls this month')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),
                
            Stat::make('Upcoming Appointments', $upcomingAppointments)
                ->description('Scheduled appointments')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
                
            Stat::make('Active Customers', $activeCustomers)
                ->description('Total customers')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
        ];
    }
}