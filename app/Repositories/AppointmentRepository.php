<?php

namespace App\Repositories;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AppointmentRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return Appointment::class;
    }
    
    /**
     * Boot the repository
     */
    protected function boot(): void
    {
        // Define default relationships for standard loading
        $this->with = ['customer', 'staff', 'branch', 'service'];
    }

    /**
     * Get appointments for date range (paginated)
     */
    public function getByDateRange(Carbon $startDate, Carbon $endDate, int $perPage = 100): LengthAwarePaginator
    {
        return $this->pushCriteria(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('starts_at', [$startDate, $endDate])
                  ->orderBy('starts_at');
        })->standard()->paginate($perPage);
    }
    
    /**
     * Process appointments by date range in chunks
     */
    public function processAppointmentsByDateRange(Carbon $startDate, Carbon $endDate, callable $processor): bool
    {
        return $this->pushCriteria(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('starts_at', [$startDate, $endDate])
                  ->orderBy('starts_at');
        })->chunkSafe(500, $processor, 'standard');
    }

    /**
     * Get appointments for staff member (paginated)
     */
    public function getByStaff(string|int $staffId, ?Carbon $date = null, int $perPage = 100): LengthAwarePaginator
    {
        return $this->pushCriteria(function ($query) use ($staffId, $date) {
            $query->where('staff_id', $staffId);
            
            if ($date) {
                $query->whereDate('starts_at', $date);
            }
            
            $query->orderBy('starts_at');
        })->standard()->paginate($perPage);
    }

    /**
     * Get appointments for customer (paginated)
     */
    public function getByCustomer(string|int $customerId, bool $onlyFuture = true, int $perPage = 50): LengthAwarePaginator
    {
        $query = $this->model->where('customer_id', $customerId);
        
        if ($onlyFuture) {
            $query->where('starts_at', '>=', now());
        }
        
        return $query
            ->with(['staff', 'service', 'branch'])
            ->orderBy('starts_at')
            ->paginate($perPage);
    }
    
    /**
     * Get appointments for customer (all - for customer history)
     */
    public function getByCustomerAll(string|int $customerId, bool $onlyFuture = true): Collection
    {
        return $this->pushCriteria(function ($query) use ($customerId, $onlyFuture) {
            $query->where('customer_id', $customerId);
            
            if ($onlyFuture) {
                $query->where('starts_at', '>=', now());
            }
            
            $query->with(['staff', 'service', 'branch'])
                  ->orderBy('starts_at');
        })->allSafe();
    }

    /**
     * Get overlapping appointments
     */
    public function getOverlapping(string|int $staffId, Carbon $startTime, Carbon $endTime, string|int|null $excludeId = null): Collection
    {
        $query = $this->model
            ->where('staff_id', $staffId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('starts_at', [$startTime, $endTime])
                  ->orWhereBetween('ends_at', [$startTime, $endTime])
                  ->orWhere(function ($q) use ($startTime, $endTime) {
                      $q->where('starts_at', '<=', $startTime)
                        ->where('ends_at', '>=', $endTime);
                  });
            });
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->get();
    }

    /**
     * Check if time slot is available
     */
    public function isTimeSlotAvailable(string|int $staffId, Carbon $startTime, Carbon $endTime): bool
    {
        return $this->getOverlapping($staffId, $startTime, $endTime)->isEmpty();
    }

    /**
     * Get upcoming appointments
     */
    public function getUpcoming(int $limit = 10): Collection
    {
        return $this->model
            ->where('starts_at', '>', now())
            ->where('status', 'scheduled')
            ->with(['customer', 'staff', 'branch', 'service'])
            ->orderBy('starts_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get appointments by status (paginated)
     */
    public function getByStatus(string $status, ?Carbon $date = null, int $perPage = 100): LengthAwarePaginator
    {
        $query = $this->model->where('status', $status);
        
        if ($date) {
            $query->whereDate('starts_at', $date);
        }
        
        return $query
            ->with(['customer', 'staff', 'branch', 'service'])
            ->orderBy('starts_at')
            ->paginate($perPage);
    }

    /**
     * Mark appointments as no-show
     */
    public function markAsNoShow(Carbon $beforeTime): int
    {
        return $this->model
            ->where('status', 'scheduled')
            ->where('starts_at', '<', $beforeTime)
            ->update(['status' => 'no_show']);
    }

    /**
     * Get appointment statistics (optimized with single query)
     */
    public function getStatistics(Carbon $startDate, Carbon $endDate): array
    {
        // Use a single aggregation query for better performance
        $stats = $this->model
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_appointments,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_appointments,
                COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_appointments,
                COUNT(CASE WHEN status = "no_show" THEN 1 END) as no_show_appointments,
                SUM(CASE WHEN status = "completed" THEN price ELSE 0 END) as total_revenue
            ')
            ->first();
            
        $completedCount = $stats->completed_appointments ?? 0;
        $totalRevenue = $stats->total_revenue ?? 0;
        
        return [
            'total_appointments' => $stats->total_appointments ?? 0,
            'completed_appointments' => $completedCount,
            'cancelled_appointments' => $stats->cancelled_appointments ?? 0,
            'no_show_appointments' => $stats->no_show_appointments ?? 0,
            'total_revenue' => $totalRevenue,
            'average_appointment_value' => $completedCount > 0 ? round($totalRevenue / $completedCount, 2) : 0,
        ];
    }

    /**
     * Search appointments (with limit for safety)
     */
    public function search(string $term, int $limit = 50): Collection
    {
        return $this->model
            ->whereHas('customer', function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%")
                      ->orWhere('phone', 'like', "%{$term}%");
            })
            ->orWhereHas('staff', function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%");
            })
            ->orWhere('notes', 'like', "%{$term}%")
            ->with(['customer', 'staff', 'branch', 'service'])
            ->orderBy('starts_at', 'desc')
            ->limit($limit)
            ->get();
    }
}