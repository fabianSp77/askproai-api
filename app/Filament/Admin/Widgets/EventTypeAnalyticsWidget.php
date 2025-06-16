<?php

namespace App\Filament\Admin\Widgets;

use App\Models\CalcomEventType;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class EventTypeAnalyticsWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '60s';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        $cacheKey = 'event_type_analytics_' . (auth()->user()->company_id ?? 'all');
        
        return Cache::remember($cacheKey, 300, function () {
            return [
                $this->getActiveEventTypesStat(),
                $this->getMostBookedEventTypeStat(),
                $this->getStaffCoverageStat(),
                $this->getUpcomingAvailabilityStat(),
            ];
        });
    }
    
    private function getActiveEventTypesStat(): Stat
    {
        $totalEventTypes = CalcomEventType::count();
        $activeEventTypes = CalcomEventType::where('is_active', true)->count();
        $withStaff = CalcomEventType::whereHas('assignedStaff')->count();
        
        $activationRate = $totalEventTypes > 0 
            ? round(($activeEventTypes / $totalEventTypes) * 100, 1) 
            : 0;
        
        return Stat::make('📅 Event-Types', $activeEventTypes . ' aktiv')
            ->description(sprintf(
                'Von %d total • %d mit Mitarbeitern',
                $totalEventTypes,
                $withStaff
            ))
            ->chart($this->getEventTypeActivityChart())
            ->color($activationRate > 80 ? 'success' : ($activationRate > 50 ? 'warning' : 'danger'))
            ->extraAttributes([
                'class' => 'ring-2 ring-primary-500/20'
            ]);
    }
    
    private function getMostBookedEventTypeStat(): Stat
    {
        $thisMonth = Carbon::now()->startOfMonth();
        
        $topEventType = DB::table('appointments')
            ->join('calcom_event_types', 'appointments.calcom_event_type_id', '=', 'calcom_event_types.id')
            ->where('appointments.starts_at', '>=', $thisMonth)
            ->select('calcom_event_types.name', DB::raw('COUNT(*) as booking_count'))
            ->groupBy('calcom_event_types.id', 'calcom_event_types.name')
            ->orderByDesc('booking_count')
            ->first();
        
        if (!$topEventType) {
            return Stat::make('🏆 Top Event-Type', 'Keine Daten')
                ->description('Noch keine Buchungen diesen Monat')
                ->color('gray');
        }
        
        // Get service name from event type name (extract last part after last hyphen)
        $nameParts = explode('-', $topEventType->name);
        $serviceName = end($nameParts);
        
        return Stat::make('🏆 Top Event-Type', $serviceName)
            ->description(sprintf(
                '%d Buchungen diesen Monat',
                $topEventType->booking_count
            ))
            ->chart($this->getTopEventTypesChart())
            ->color('success');
    }
    
    private function getStaffCoverageStat(): Stat
    {
        $totalStaff = Staff::where('active', true)->count();
        $staffWithEventTypes = Staff::where('active', true)
            ->whereHas('eventTypes')
            ->count();
        
        $coverageRate = $totalStaff > 0 
            ? round(($staffWithEventTypes / $totalStaff) * 100, 1) 
            : 0;
        
        // Average event types per staff
        $avgEventTypesPerStaff = $staffWithEventTypes > 0
            ? round(
                DB::table('staff_event_types')
                    ->join('staff', 'staff_event_types.staff_id', '=', 'staff.id')
                    ->where('staff.active', true)
                    ->count() / $staffWithEventTypes, 
                1
            )
            : 0;
        
        return Stat::make('👥 Mitarbeiter-Abdeckung', $coverageRate . '%')
            ->description(sprintf(
                '%d von %d • Ø %.1f Events/MA',
                $staffWithEventTypes,
                $totalStaff,
                $avgEventTypesPerStaff
            ))
            ->chart($this->getStaffCoverageChart())
            ->color($coverageRate > 80 ? 'success' : ($coverageRate > 50 ? 'warning' : 'danger'));
    }
    
    private function getUpcomingAvailabilityStat(): Stat
    {
        $next7Days = Carbon::now()->addDays(7);
        
        // Calculate total available slots in next 7 days
        // This is a simplified calculation - in reality would check actual Cal.com availability
        $workingDays = 5; // Mon-Fri
        $hoursPerDay = 8;
        $slotsPerHour = 2; // 30-min slots
        $activeStaff = Staff::where('active', true)->whereHas('eventTypes')->count();
        
        $totalPossibleSlots = $workingDays * $hoursPerDay * $slotsPerHour * $activeStaff;
        
        // Get booked slots
        $bookedSlots = Appointment::whereBetween('starts_at', [Carbon::now(), $next7Days])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->count();
        
        $availableSlots = max(0, $totalPossibleSlots - $bookedSlots);
        $utilizationRate = $totalPossibleSlots > 0 
            ? round(($bookedSlots / $totalPossibleSlots) * 100, 1) 
            : 0;
        
        return Stat::make('🗓️ Verfügbarkeit (7 Tage)', $availableSlots . ' Slots')
            ->description(sprintf(
                'Auslastung: %.1f%% • %d gebucht',
                $utilizationRate,
                $bookedSlots
            ))
            ->chart($this->getAvailabilityChart())
            ->color($utilizationRate < 70 ? 'success' : ($utilizationRate < 90 ? 'warning' : 'danger'))
            ->extraAttributes([
                'class' => 'bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20'
            ]);
    }
    
    // Chart generation methods
    private function getEventTypeActivityChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $count = Appointment::whereDate('starts_at', $date)
                ->whereNotNull('calcom_event_type_id')
                ->count();
            $data[] = $count;
        }
        return $data;
    }
    
    private function getTopEventTypesChart(): array
    {
        $thisMonth = Carbon::now()->startOfMonth();
        
        $topEventTypes = DB::table('appointments')
            ->join('calcom_event_types', 'appointments.calcom_event_type_id', '=', 'calcom_event_types.id')
            ->where('appointments.starts_at', '>=', $thisMonth)
            ->select(DB::raw('COUNT(*) as count'))
            ->groupBy('calcom_event_types.id')
            ->orderByDesc('count')
            ->limit(7)
            ->pluck('count')
            ->toArray();
        
        // Pad with zeros if less than 7
        return array_pad($topEventTypes, 7, 0);
    }
    
    private function getStaffCoverageChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subMonths($i)->endOfMonth();
            $total = Staff::where('created_at', '<=', $date)->where('active', true)->count();
            $withEvents = Staff::where('created_at', '<=', $date)
                ->where('active', true)
                ->whereHas('eventTypes')
                ->count();
            $data[] = $total > 0 ? round(($withEvents / $total) * 100) : 0;
        }
        return $data;
    }
    
    private function getAvailabilityChart(): array
    {
        $data = [];
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::today()->addDays($i);
            
            // Skip weekends
            if ($date->isWeekend()) {
                $data[] = 0;
                continue;
            }
            
            $booked = Appointment::whereDate('starts_at', $date)
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->count();
            
            // Assume 8 hours * 2 slots per hour * active staff
            $activeStaff = Staff::where('active', true)->whereHas('eventTypes')->count();
            $totalSlots = 16 * $activeStaff;
            $available = max(0, $totalSlots - $booked);
            
            $data[] = $available;
        }
        return $data;
    }
}