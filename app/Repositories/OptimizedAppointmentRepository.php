<?php

namespace App\Repositories;

use App\Models\Appointment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Optimized Appointment Repository
 * Implements eager loading, query optimization, and caching
 */
class OptimizedAppointmentRepository
{
    /**
     * Get appointments with all relations efficiently loaded (paginated)
     */
    public function getAppointmentsWithRelations(array $filters = [], int $perPage = 100): LengthAwarePaginator
    {
        $query = Appointment::query()
            ->with([
                'customer:id,name,phone,email',
                'staff:id,name,avatar_url',
                'branch:id,name,address',
                'service:id,name,duration,price',
                'eventType:id,name,slug'
            ])
            ->select([
                'id',
                'customer_id',
                'staff_id',
                'branch_id',
                'service_id',
                'event_type_id',
                'start_time',
                'end_time',
                'status',
                'notes',
                'created_at'
            ]);
        
        // Apply filters
        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        
        if (isset($filters['date'])) {
            $query->whereDate('start_time', $filters['date']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        return $query->paginate($perPage);
    }
    
    /**
     * Process appointments with relations in chunks
     */
    public function processAppointmentsWithRelations(array $filters, callable $processor, int $chunkSize = 500): bool
    {
        $query = Appointment::query()
            ->with([
                'customer:id,name,phone,email',
                'staff:id,name,avatar_url',
                'branch:id,name,address',
                'service:id,name,duration,price',
                'eventType:id,name,slug'
            ])
            ->select([
                'id',
                'customer_id',
                'staff_id',
                'branch_id',
                'service_id',
                'event_type_id',
                'start_time',
                'end_time',
                'status',
                'notes',
                'created_at'
            ]);
        
        // Apply filters
        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        
        if (isset($filters['date'])) {
            $query->whereDate('start_time', $filters['date']);
        }
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        return $query->chunk($chunkSize, $processor);
    }
    
    /**
     * Get today's appointments grouped by branch with optimized query
     */
    public function getTodaysAppointmentsByBranch(int $companyId): Collection
    {
        $cacheKey = "appointments:today:company:{$companyId}";
        
        return Cache::remember($cacheKey, 300, function() use ($companyId) {
            return DB::table('appointments as a')
                ->join('branches as b', 'a.branch_id', '=', 'b.id')
                ->join('customers as c', 'a.customer_id', '=', 'c.id')
                ->join('staff as s', 'a.staff_id', '=', 's.id')
                ->leftJoin('services as sv', 'a.service_id', '=', 'sv.id')
                ->where('b.company_id', $companyId)
                ->whereDate('a.start_time', today())
                ->where('a.status', '!=', 'cancelled')
                ->select([
                    'a.id',
                    'a.start_time',
                    'a.end_time',
                    'a.status',
                    'b.id as branch_id',
                    'b.name as branch_name',
                    'c.name as customer_name',
                    'c.phone as customer_phone',
                    's.id as staff_id',
                    's.name as staff_name',
                    'sv.name as service_name'
                ])
                ->orderBy('b.id')
                ->orderBy('a.start_time')
                ->get()
                ->groupBy('branch_id');
        });
    }
    
    /**
     * Get appointment statistics with single optimized query
     */
    public function getAppointmentStats(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $stats = DB::table('appointments as a')
            ->join('branches as b', 'a.branch_id', '=', 'b.id')
            ->where('b.company_id', $companyId)
            ->whereBetween('a.start_time', [$startDate, $endDate])
            ->select([
                DB::raw('COUNT(a.id) as total'),
                DB::raw('COUNT(CASE WHEN a.status = "completed" THEN 1 END) as completed'),
                DB::raw('COUNT(CASE WHEN a.status = "cancelled" THEN 1 END) as cancelled'),
                DB::raw('COUNT(CASE WHEN a.status = "no_show" THEN 1 END) as no_show'),
                DB::raw('COUNT(DISTINCT a.customer_id) as unique_customers'),
                DB::raw('COUNT(DISTINCT DATE(a.start_time)) as days_with_appointments'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, a.start_time, a.end_time)) as avg_duration_minutes')
            ])
            ->first();
        
        return [
            'total' => $stats->total ?? 0,
            'completed' => $stats->completed ?? 0,
            'cancelled' => $stats->cancelled ?? 0,
            'no_show' => $stats->no_show ?? 0,
            'unique_customers' => $stats->unique_customers ?? 0,
            'days_with_appointments' => $stats->days_with_appointments ?? 0,
            'avg_duration_minutes' => round($stats->avg_duration_minutes ?? 0, 1),
            'completion_rate' => $stats->total > 0 
                ? round(($stats->completed / $stats->total) * 100, 1) 
                : 0,
        ];
    }
    
    /**
     * Bulk check availability for multiple staff members
     */
    public function bulkCheckStaffAvailability(array $staffIds, Carbon $date): array
    {
        // Get all appointments for the given staff on the date
        $appointments = DB::table('appointments')
            ->whereIn('staff_id', $staffIds)
            ->whereDate('start_time', $date)
            ->where('status', '!=', 'cancelled')
            ->select('staff_id', 'start_time', 'end_time')
            ->get()
            ->groupBy('staff_id');
        
        // Get working hours for all staff
        $workingHours = DB::table('working_hours')
            ->whereIn('staff_id', $staffIds)
            ->where('day_of_week', $date->dayOfWeek)
            ->select('staff_id', 'start_time', 'end_time', 'is_working')
            ->get()
            ->keyBy('staff_id');
        
        $availability = [];
        
        foreach ($staffIds as $staffId) {
            $staffAppointments = $appointments->get($staffId, collect());
            $staffHours = $workingHours->get($staffId);
            
            if (!$staffHours || !$staffHours->is_working) {
                $availability[$staffId] = ['available' => false, 'slots' => []];
                continue;
            }
            
            // Calculate available slots
            $slots = $this->calculateAvailableSlots(
                $staffHours->start_time,
                $staffHours->end_time,
                $staffAppointments,
                30 // 30 minute slots
            );
            
            $availability[$staffId] = [
                'available' => count($slots) > 0,
                'slots' => $slots,
                'total_slots' => count($slots)
            ];
        }
        
        return $availability;
    }
    
    /**
     * Get upcoming appointments for a customer with minimal queries (with limit for safety)
     */
    public function getCustomerUpcomingAppointments(int $customerId, int $limit = 5): Collection
    {
        return Appointment::with([
                'staff:id,name',
                'branch:id,name,address',
                'service:id,name,duration'
            ])
            ->where('customer_id', $customerId)
            ->where('start_time', '>', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Calculate available time slots
     */
    private function calculateAvailableSlots(
        string $startTime,
        string $endTime,
        Collection $appointments,
        int $slotDuration
    ): array {
        $slots = [];
        $current = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        
        while ($current->copy()->addMinutes($slotDuration)->lte($end)) {
            $slotEnd = $current->copy()->addMinutes($slotDuration);
            
            // Check if slot conflicts with any appointment
            $hasConflict = $appointments->contains(function($apt) use ($current, $slotEnd) {
                $aptStart = Carbon::parse($apt->start_time);
                $aptEnd = Carbon::parse($apt->end_time);
                
                return $current->lt($aptEnd) && $slotEnd->gt($aptStart);
            });
            
            if (!$hasConflict) {
                $slots[] = [
                    'start' => $current->format('H:i'),
                    'end' => $slotEnd->format('H:i')
                ];
            }
            
            $current->addMinutes($slotDuration);
        }
        
        return $slots;
    }
}