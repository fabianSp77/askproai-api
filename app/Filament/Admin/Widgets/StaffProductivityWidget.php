<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Staff;
use App\Models\Appointment;
use App\Models\WorkingHour;
use Filament\Widgets\Widget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StaffProductivityWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.staff-productivity';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;
    
    public function getProductivityData(): array
    {
        return [
            'overview' => $this->getOverviewStats(),
            'topPerformers' => $this->getTopPerformers(),
            'workload' => $this->getWorkloadDistribution(),
            'availability' => $this->getAvailabilityMetrics(),
            'skills' => $this->getSkillsMatrix(),
        ];
    }
    
    private function getOverviewStats(): array
    {
        $totalStaff = Staff::count();
        $activeStaff = Staff::where('active', true)->count();
        $bookableStaff = Staff::where('is_bookable', true)->where('active', true)->count();
        
        return [
            'total_staff' => $totalStaff,
            'active_staff' => $activeStaff,
            'bookable_staff' => $bookableStaff,
            'avg_appointments_per_staff' => $this->calculateAverageAppointments(),
            'total_appointments_today' => $this->getTodaysAppointments(),
            'utilization_rate' => $this->calculateUtilizationRate(),
        ];
    }
    
    private function getTopPerformers(): array
    {
        return Staff::select('staff.id', 'staff.name', 'staff.home_branch_id', 'staff.active')
            ->selectRaw('COUNT(DISTINCT appointments.id) as appointment_count')
            ->selectRaw('COUNT(DISTINCT appointments.customer_id) as unique_customers')
            ->selectRaw('AVG(CASE WHEN appointments.status = "completed" THEN 1 ELSE 0 END) * 100 as completion_rate')
            ->selectRaw('NULL as avg_rating')
            ->leftJoin('appointments', 'staff.id', '=', 'appointments.staff_id')
            ->where('appointments.created_at', '>=', Carbon::now()->startOfMonth())
            ->where('staff.active', true)
            ->groupBy('staff.id', 'staff.name', 'staff.home_branch_id', 'staff.active')
            ->orderByDesc('appointment_count')
            ->limit(10)
            ->get()
            ->map(function ($staff) {
                // Load relationships
                $staff->load(['services', 'homeBranch']);
                
                $revenue = Appointment::where('staff_id', $staff->id)
                    ->join('services', 'appointments.service_id', '=', 'services.id')
                    ->where('appointments.status', 'completed')
                    ->where('appointments.created_at', '>=', Carbon::now()->startOfMonth())
                    ->sum('services.price');
                    
                $serviceCount = $staff->services()->count();
                $workingHours = $this->calculateMonthlyWorkingHours($staff);
                $productivity = $workingHours > 0 ? ($staff->appointment_count / $workingHours) * 8 : 0;
                
                return [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'branch' => $staff->homeBranch?->name ?? 'Nicht zugewiesen',
                    'appointments' => $staff->appointment_count,
                    'unique_customers' => $staff->unique_customers,
                    'completion_rate' => round($staff->completion_rate, 1),
                    'avg_rating' => $staff->avg_rating ? round($staff->avg_rating, 1) : null,
                    'revenue' => $revenue,
                    'services' => $serviceCount,
                    'productivity_score' => round($productivity * 100),
                ];
            })
            ->toArray();
    }
    
    private function getWorkloadDistribution(): array
    {
        $staffWorkloads = Staff::where('active', true)
            ->get()
            ->map(function ($staff) {
                $todayAppointments = Appointment::where('staff_id', $staff->id)
                    ->whereDate('starts_at', Carbon::today())
                    ->count();
                    
                $weekAppointments = Appointment::where('staff_id', $staff->id)
                    ->whereBetween('starts_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                    ->count();
                    
                $monthAppointments = Appointment::where('staff_id', $staff->id)
                    ->whereMonth('starts_at', Carbon::now()->month)
                    ->count();
                    
                $avgDuration = Appointment::where('staff_id', $staff->id)
                    ->join('services', 'appointments.service_id', '=', 'services.id')
                    ->avg('services.duration') ?? 30;
                    
                return [
                    'staff' => $staff->name,
                    'branch' => $staff->homeBranch?->name ?? 'Nicht zugewiesen',
                    'today' => $todayAppointments,
                    'week' => $weekAppointments,
                    'month' => $monthAppointments,
                    'avg_duration' => round($avgDuration),
                    'workload_level' => $this->calculateWorkloadLevel($todayAppointments),
                ];
            })
            ->sortByDesc('month')
            ->take(15)
            ->values()
            ->toArray();
            
        return $staffWorkloads;
    }
    
    private function getAvailabilityMetrics(): array
    {
        $dayOfWeek = Carbon::now()->dayOfWeek;
        $currentHour = Carbon::now()->hour;
        
        $availableNow = Staff::where('active', true)
            ->where('is_bookable', true)
            ->whereHas('workingHours', function ($query) use ($dayOfWeek, $currentHour) {
                $query->where('weekday', $dayOfWeek)
                    ->whereRaw('? BETWEEN HOUR(start) AND HOUR(end)', [$currentHour]);
            })
            ->whereDoesntHave('appointments', function ($query) {
                $query->where('starts_at', '<=', Carbon::now())
                    ->where('ends_at', '>=', Carbon::now());
            })
            ->count();
            
        $totalBookable = Staff::where('active', true)->where('is_bookable', true)->count();
        
        $availabilityByDay = [];
        for ($day = 0; $day < 7; $day++) {
            $availableCount = Staff::where('active', true)
                ->where('is_bookable', true)
                ->whereHas('workingHours', function ($query) use ($day) {
                    $query->where('weekday', $day);
                })
                ->count();
                
            $availabilityByDay[] = [
                'day' => $this->getDayName($day),
                'available' => $availableCount,
                'percentage' => $totalBookable > 0 ? round(($availableCount / $totalBookable) * 100, 1) : 0,
            ];
        }
        
        return [
            'available_now' => $availableNow,
            'total_bookable' => $totalBookable,
            'availability_rate' => $totalBookable > 0 ? round(($availableNow / $totalBookable) * 100, 1) : 0,
            'by_day' => $availabilityByDay,
        ];
    }
    
    private function getSkillsMatrix(): array
    {
        $services = DB::table('services')
            ->select('services.id', 'services.name')
            ->selectRaw('COUNT(DISTINCT staff_services.staff_id) as staff_count')
            ->leftJoin('staff_services', 'services.id', '=', 'staff_services.service_id')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('staff_count')
            ->limit(10)
            ->get();
            
        $totalStaff = Staff::where('active', true)->count();
        
        return $services->map(function ($service) use ($totalStaff) {
            return [
                'service' => $service->name,
                'staff_count' => $service->staff_count,
                'coverage' => $totalStaff > 0 ? round(($service->staff_count / $totalStaff) * 100, 1) : 0,
            ];
        })->toArray();
    }
    
    private function calculateAverageAppointments(): float
    {
        $activeStaff = Staff::where('active', true)->count();
        if ($activeStaff === 0) return 0;
        
        $totalAppointments = Appointment::whereIn('staff_id', Staff::where('active', true)->pluck('id'))
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();
            
        return round($totalAppointments / $activeStaff, 1);
    }
    
    private function getTodaysAppointments(): int
    {
        return Appointment::whereIn('staff_id', Staff::pluck('id'))
            ->whereDate('starts_at', Carbon::today())
            ->count();
    }
    
    private function calculateUtilizationRate(): float
    {
        // Get all working hours (no is_available column exists)
        $totalWorkingHours = WorkingHour::all()
            ->sum(function ($wh) {
                return Carbon::parse($wh->end)->diffInHours(Carbon::parse($wh->start));
            });
            
        $bookedHours = Appointment::whereMonth('starts_at', Carbon::now()->month)
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->sum(DB::raw('IFNULL(services.duration, services.default_duration_minutes) / 60'));
            
        return $totalWorkingHours > 0 ? round(($bookedHours / $totalWorkingHours) * 100, 1) : 0;
    }
    
    private function calculateMonthlyWorkingHours($staff): int
    {
        $workingHours = WorkingHour::where('staff_id', $staff->id)
            ->get();
            
        $totalHours = 0;
        foreach ($workingHours as $wh) {
            $hoursPerDay = Carbon::parse($wh->end)->diffInHours(Carbon::parse($wh->start));
            $daysInMonth = 4; // Approximate weeks in month
            $totalHours += $hoursPerDay * $daysInMonth;
        }
        
        return $totalHours;
    }
    
    private function calculateWorkloadLevel($todayAppointments): array
    {
        if ($todayAppointments >= 8) {
            return ['level' => 'Überlastet', 'color' => 'red'];
        } elseif ($todayAppointments >= 6) {
            return ['level' => 'Hoch', 'color' => 'orange'];
        } elseif ($todayAppointments >= 3) {
            return ['level' => 'Normal', 'color' => 'green'];
        } else {
            return ['level' => 'Niedrig', 'color' => 'blue'];
        }
    }
    
    private function getDayName($day): string
    {
        $days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
        return $days[$day];
    }
}