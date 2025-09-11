<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use App\Models\CalcomEventType;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CalcomSyncStatusWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected static ?string $pollingInterval = '60s';
    
    protected function getStats(): array
    {
        // Get Cal.com appointments
        $calcomAppointments = Appointment::where('source', 'cal.com')->count();
        $todayAppointments = Appointment::where('source', 'cal.com')
            ->whereDate('starts_at', Carbon::today())
            ->count();
        $upcomingAppointments = Appointment::where('source', 'cal.com')
            ->where('starts_at', '>', now())
            ->count();
        
        // Get event types
        $eventTypes = CalcomEventType::count();
        $activeEventTypes = CalcomEventType::where('is_active', true)->count();
        
        // Get last sync time from cache or calculate from latest appointment
        $lastSync = Cache::get('calcom_last_sync', function () {
            $latest = Appointment::where('source', 'cal.com')
                ->latest('created_at')
                ->first();
            return $latest ? $latest->created_at : null;
        });
        
        // Calculate trends
        $lastWeekAppointments = Appointment::where('source', 'cal.com')
            ->where('created_at', '>=', now()->subWeek())
            ->count();
        $previousWeekAppointments = Appointment::where('source', 'cal.com')
            ->whereBetween('created_at', [now()->subWeeks(2), now()->subWeek()])
            ->count();
        
        $trend = $previousWeekAppointments > 0 
            ? round((($lastWeekAppointments - $previousWeekAppointments) / $previousWeekAppointments) * 100, 1)
            : 0;
        
        return [
            Stat::make('Cal.com Bookings', $calcomAppointments)
                ->description($upcomingAppointments . ' upcoming')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart($this->getAppointmentChart())
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-primary-500',
                ]),
            
            Stat::make('Today\'s Appointments', $todayAppointments)
                ->description($lastSync ? 'Last sync: ' . $lastSync->diffForHumans() : 'Never synced')
                ->descriptionIcon($todayAppointments > 0 ? 'heroicon-m-calendar' : 'heroicon-m-calendar-days')
                ->color($todayAppointments > 0 ? 'success' : 'gray')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-success-500',
                ]),
            
            Stat::make('Event Types', $eventTypes)
                ->description($activeEventTypes . ' active')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('info')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-info-500',
                ]),
            
            Stat::make('Weekly Trend', $trend . '%')
                ->description('vs previous week')
                ->descriptionIcon($trend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($trend >= 0 ? 'success' : 'danger')
                ->chart($this->getWeeklyTrendChart())
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-warning-500',
                ]),
        ];
    }
    
    protected function getAppointmentChart(): array
    {
        // Get appointments for the last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = Appointment::where('source', 'cal.com')
                ->whereDate('created_at', $date)
                ->count();
            $data[] = $count;
        }
        
        return $data;
    }
    
    protected function getWeeklyTrendChart(): array
    {
        // Get weekly appointment counts for the last 8 weeks
        $data = [];
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            $count = Appointment::where('source', 'cal.com')
                ->whereBetween('starts_at', [$weekStart, $weekEnd])
                ->count();
            $data[] = $count;
        }
        
        return $data;
    }
}