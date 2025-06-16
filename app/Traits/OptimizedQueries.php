<?php

namespace App\Traits;

use App\Services\QueryOptimizer;
use App\Services\QueryCache;
use Illuminate\Database\Eloquent\Builder;

trait OptimizedQueries
{
    /**
     * Get appointments with optimized query
     */
    public function getOptimizedAppointments($filters = [])
    {
        $optimizer = new QueryOptimizer();
        
        $query = $this->appointments()
            ->upcoming()
            ->withRelations();
        
        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('starts_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('starts_at', '<=', $filters['date_to']);
        }
        
        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }
        
        // Force use of date index for better performance
        if (isset($filters['date_from']) || isset($filters['date_to'])) {
            $optimizer->forceIndex($query, 'appointments', 'idx_appointments_dates');
        }
        
        // Add query hint for large result sets
        if (!isset($filters['limit']) || $filters['limit'] > 100) {
            $optimizer->addQueryHint($query, 'big_result');
        }
        
        return $query;
    }
    
    /**
     * Get customers with optimized search
     */
    public function searchCustomersOptimized($search)
    {
        $optimizer = new QueryOptimizer();
        
        $query = $this->customers()
            ->search($search)
            ->withAppointmentCount();
        
        // Force use of phone index if search looks like a phone number
        if (preg_match('/^[\d\s\-\+\(\)]+$/', $search)) {
            $optimizer->forceIndex($query, 'customers', 'idx_customers_phone');
        }
        
        return $optimizer->optimizeCustomerQuery($query);
    }
    
    /**
     * Get staff availability with caching
     */
    public function getStaffAvailabilityOptimized($date, $serviceId = null)
    {
        $cache = new QueryCache();
        $cacheKey = "staff_availability:{$this->id}:{$date}:" . ($serviceId ?? 'all');
        
        return cache()->remember($cacheKey, 300, function () use ($date, $serviceId) {
            $optimizer = new QueryOptimizer();
            
            $query = $this->staff()
                ->available()
                ->withAppointmentCount($date, $date);
            
            if ($serviceId) {
                $query->withServices($serviceId);
            }
            
            // Force use of compound index
            $optimizer->forceIndex($query, 'staff', 'idx_staff_bookable_active');
            
            return $query->get()->map(function ($staff) use ($date) {
                $appointments = $staff->appointments()
                    ->whereDate('starts_at', $date)
                    ->scheduled()
                    ->get(['starts_at', 'ends_at']);
                
                return [
                    'staff' => $staff,
                    'booked_slots' => $appointments->map(function ($apt) {
                        return [
                            'start' => $apt->starts_at->format('H:i'),
                            'end' => $apt->ends_at->format('H:i')
                        ];
                    }),
                    'available_slots' => $this->calculateAvailableSlots($staff, $date, $appointments)
                ];
            });
        });
    }
    
    /**
     * Get dashboard statistics with aggressive caching
     */
    public function getDashboardStatsOptimized($dateRange = 'month')
    {
        $cache = new QueryCache();
        
        return [
            'appointments' => $cache->getAppointmentStats($this->id, $dateRange),
            'customers' => $cache->getCustomerMetrics($this->id),
            'calls' => $cache->getCallStats($this->id),
            'staff_performance' => $cache->getStaffPerformance($this->id),
            'branch_comparison' => $cache->getBranchComparison($this->id, $dateRange),
        ];
    }
    
    /**
     * Bulk update with optimized query
     */
    public function bulkUpdateAppointmentsOptimized($ids, $data)
    {
        $optimizer = new QueryOptimizer();
        
        // Use raw query for better performance on bulk updates
        $query = $this->appointments()->whereIn('id', $ids);
        
        // Disable query cache for this update
        $optimizer->addQueryHint($query, 'no_cache');
        
        // Perform update
        $updated = $query->update($data);
        
        // Clear relevant caches
        $cache = new QueryCache();
        $cache->clearCompanyCache($this->id);
        
        return $updated;
    }
    
    /**
     * Get paginated results with cursor pagination for large datasets
     */
    public function getPaginatedOptimized(Builder $query, $perPage = 15)
    {
        $optimizer = new QueryOptimizer();
        
        return $optimizer->optimizePagination($query, $perPage);
    }
    
    /**
     * Calculate available slots helper
     */
    private function calculateAvailableSlots($staff, $date, $appointments)
    {
        // This would contain logic to calculate available time slots
        // based on working hours and existing appointments
        return [];
    }
}