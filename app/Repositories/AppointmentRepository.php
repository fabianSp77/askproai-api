<?php

namespace App\Repositories;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
     * Get appointments for date range
     */
    public function getByDateRange(Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->pushCriteria(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('starts_at', [$startDate, $endDate])
                  ->orderBy('starts_at');
        })->standard()->all();
    }

    /**
     * Get appointments for staff member
     */
    public function getByStaff(string|int $staffId, ?Carbon $date = null): Collection
    {
        return $this->pushCriteria(function ($query) use ($staffId, $date) {
            $query->where('staff_id', $staffId);
            
            if ($date) {
                $query->whereDate('starts_at', $date);
            }
            
            $query->orderBy('starts_at');
        })->standard()->all();
    }

    /**
     * Get appointments for customer
     */
    public function getByCustomer(string|int $customerId, bool $onlyFuture = true): Collection
    {
        $query = $this->model->where('customer_id', $customerId);
        
        if ($onlyFuture) {
            $query->where('starts_at', '>=', now());
        }
        
        return $query
            ->with(['staff', 'service', 'branch'])
            ->orderBy('starts_at')
            ->get();
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
     * Get appointments by status
     */
    public function getByStatus(string $status, ?Carbon $date = null): Collection
    {
        $query = $this->model->where('status', $status);
        
        if ($date) {
            $query->whereDate('starts_at', $date);
        }
        
        return $query
            ->with(['customer', 'staff', 'branch', 'service'])
            ->orderBy('starts_at')
            ->get();
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
     * Get appointment statistics
     */
    public function getStatistics(Carbon $startDate, Carbon $endDate): array
    {
        $appointments = $this->model
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->get();
            
        $completed = $appointments->where('status', 'completed');
        
        return [
            'total_appointments' => $appointments->count(),
            'completed_appointments' => $completed->count(),
            'cancelled_appointments' => $appointments->where('status', 'cancelled')->count(),
            'no_show_appointments' => $appointments->where('status', 'no_show')->count(),
            'total_revenue' => $completed->sum('price'),
            'average_appointment_value' => $completed->count() > 0 ? $completed->sum('price') / $completed->count() : 0,
        ];
    }

    /**
     * Search appointments
     */
    public function search(string $term): Collection
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
            ->limit(50)
            ->get();
    }
}