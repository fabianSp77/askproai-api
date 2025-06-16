<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\WorkingHour;
use App\Services\CacheService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StaffService
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Get staff schedules with caching
     */
    public function getSchedule(int $staffId): array
    {
        return $this->cacheService->getStaffSchedules($staffId, function () use ($staffId) {
            $staff = Staff::with(['workingHours', 'branches', 'services'])->find($staffId);
            
            if (!$staff) {
                return [];
            }

            // Group working hours by day
            $schedule = [];
            foreach ($staff->workingHours as $workingHour) {
                $dayName = $this->getDayName($workingHour->day_of_week);
                $schedule[$dayName][] = [
                    'start_time' => $workingHour->start_time,
                    'end_time' => $workingHour->end_time,
                    'break_start' => $workingHour->break_start,
                    'break_end' => $workingHour->break_end,
                    'is_active' => $workingHour->is_active,
                ];
            }

            return [
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'is_bookable' => $staff->is_bookable,
                'active' => $staff->active,
                'schedule' => $schedule,
                'branches' => $staff->branches->pluck('name', 'id')->toArray(),
                'services' => $staff->services->pluck('name', 'id')->toArray(),
                'availability_mode' => $staff->availability_mode,
                'calendar_mode' => $staff->calendar_mode,
            ];
        });
    }

    /**
     * Get weekly schedule for staff
     */
    public function getWeeklySchedule(int $staffId, Carbon $weekStart = null): array
    {
        $weekStart = $weekStart ?? Carbon::now()->startOfWeek();
        $cacheKey = $staffId . ':' . $weekStart->format('Y-W');
        
        return $this->cacheService->getStaffSchedules($cacheKey, function () use ($staffId, $weekStart) {
            $staff = Staff::with(['workingHours', 'appointments' => function ($query) use ($weekStart) {
                $query->whereBetween('starts_at', [
                    $weekStart->copy()->startOfWeek(),
                    $weekStart->copy()->endOfWeek()
                ]);
            }])->find($staffId);

            if (!$staff) {
                return [];
            }

            $weekSchedule = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $weekStart->copy()->addDays($i);
                $dayOfWeek = $date->dayOfWeek;
                
                // Get working hours for this day
                $workingHours = $staff->workingHours
                    ->where('day_of_week', $dayOfWeek)
                    ->where('is_active', true)
                    ->map(function ($wh) {
                        return [
                            'start' => $wh->start_time,
                            'end' => $wh->end_time,
                            'break_start' => $wh->break_start,
                            'break_end' => $wh->break_end,
                        ];
                    })->toArray();

                // Get appointments for this day
                $appointments = $staff->appointments
                    ->filter(function ($apt) use ($date) {
                        return $apt->starts_at->isSameDay($date);
                    })
                    ->map(function ($apt) {
                        return [
                            'id' => $apt->id,
                            'start' => $apt->starts_at->format('H:i'),
                            'end' => $apt->ends_at->format('H:i'),
                            'service' => $apt->service?->name,
                            'customer' => $apt->customer?->name,
                            'status' => $apt->status,
                        ];
                    })->toArray();

                $weekSchedule[$date->format('Y-m-d')] = [
                    'date' => $date->format('Y-m-d'),
                    'day_name' => $date->format('l'),
                    'is_working_day' => !empty($workingHours),
                    'working_hours' => $workingHours,
                    'appointments' => $appointments,
                    'appointment_count' => count($appointments),
                ];
            }

            return [
                'staff_id' => $staff->id,
                'staff_name' => $staff->name,
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekStart->copy()->endOfWeek()->format('Y-m-d'),
                'schedule' => $weekSchedule,
            ];
        });
    }

    /**
     * Update staff working hours
     */
    public function updateWorkingHours(int $staffId, array $workingHours): bool
    {
        $staff = Staff::find($staffId);
        
        if (!$staff) {
            Log::error('Staff not found for working hours update', ['staff_id' => $staffId]);
            return false;
        }

        // Delete existing working hours
        WorkingHour::where('staff_id', $staffId)->delete();

        // Create new working hours
        foreach ($workingHours as $dayOfWeek => $hours) {
            foreach ($hours as $period) {
                WorkingHour::create([
                    'staff_id' => $staffId,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $period['start_time'],
                    'end_time' => $period['end_time'],
                    'break_start' => $period['break_start'] ?? null,
                    'break_end' => $period['break_end'] ?? null,
                    'is_active' => $period['is_active'] ?? true,
                ]);
            }
        }

        // Clear cache after update
        $this->cacheService->clearStaffCache($staffId);

        return true;
    }

    /**
     * Get all staff members for a company
     */
    public function getCompanyStaff(int $companyId): Collection
    {
        return Staff::where('company_id', $companyId)
            ->with(['branches', 'services', 'workingHours'])
            ->active()
            ->get();
    }

    /**
     * Get available staff for a specific service and branch
     */
    public function getAvailableStaff(int $serviceId, int $branchId, Carbon $date): Collection
    {
        return Staff::whereHas('services', function ($query) use ($serviceId) {
                $query->where('services.id', $serviceId);
            })
            ->whereHas('branches', function ($query) use ($branchId) {
                $query->where('branches.id', $branchId);
            })
            ->whereHas('workingHours', function ($query) use ($date) {
                $query->where('day_of_week', $date->dayOfWeek)
                    ->where('is_active', true);
            })
            ->where('active', true)
            ->where('is_bookable', true)
            ->get();
    }

    /**
     * Get day name from day of week number
     */
    protected function getDayName(int $dayOfWeek): string
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$dayOfWeek] ?? 'Unknown';
    }
}