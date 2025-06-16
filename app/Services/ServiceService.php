<?php

namespace App\Services;

use App\Models\Service;
use App\Services\CacheService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ServiceService
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Get services list with caching
     */
    public function getServicesList(int $companyId, ?int $branchId = null): Collection
    {
        return $this->cacheService->getServiceLists($companyId, $branchId, function () use ($companyId, $branchId) {
            $query = Service::where('company_id', $companyId)
                ->where('active', true)
                ->with(['staff', 'branches']);

            if ($branchId) {
                $query->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id'); // Include company-wide services
                });
            }

            return $query->orderBy('category')
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->get();
        });
    }

    /**
     * Get services by category
     */
    public function getServicesByCategory(int $companyId, string $category): Collection
    {
        return $this->getServicesList($companyId)
            ->filter(function ($service) use ($category) {
                return $service->category === $category;
            });
    }

    /**
     * Get online bookable services
     */
    public function getOnlineBookableServices(int $companyId, ?int $branchId = null): Collection
    {
        return $this->getServicesList($companyId, $branchId)
            ->filter(function ($service) {
                return $service->is_online_bookable;
            });
    }

    /**
     * Get service by ID with caching
     */
    public function getService(int $serviceId): ?Service
    {
        // Use a custom cache key for individual services
        $cacheKey = 'service:' . $serviceId;
        
        return cache()->remember($cacheKey, CacheService::TTL_SERVICE_LISTS, function () use ($serviceId) {
            return Service::with(['staff', 'branches'])->find($serviceId);
        });
    }

    /**
     * Create new service
     */
    public function create(array $data): Service
    {
        $service = Service::create($data);

        // Clear cache for company
        $this->cacheService->clearCompanyCache($service->company_id);

        return $service;
    }

    /**
     * Update service
     */
    public function update(int $serviceId, array $data): bool
    {
        $service = Service::find($serviceId);
        
        if (!$service) {
            Log::error('Service not found for update', ['service_id' => $serviceId]);
            return false;
        }

        $result = $service->update($data);

        // Clear cache after update
        if ($result) {
            $this->cacheService->clearCompanyCache($service->company_id);
            cache()->forget('service:' . $serviceId);
        }

        return $result;
    }

    /**
     * Delete service
     */
    public function delete(int $serviceId): bool
    {
        $service = Service::find($serviceId);
        
        if (!$service) {
            Log::error('Service not found for deletion', ['service_id' => $serviceId]);
            return false;
        }

        $companyId = $service->company_id;
        $result = $service->delete();

        // Clear cache after deletion
        if ($result) {
            $this->cacheService->clearCompanyCache($companyId);
            cache()->forget('service:' . $serviceId);
        }

        return $result;
    }

    /**
     * Get services grouped by category
     */
    public function getServicesGroupedByCategory(int $companyId, ?int $branchId = null): array
    {
        $services = $this->getServicesList($companyId, $branchId);
        
        return $services->groupBy('category')
            ->map(function ($categoryServices) {
                return $categoryServices->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'description' => $service->description,
                        'price' => $service->price,
                        'duration' => $service->default_duration_minutes,
                        'is_online_bookable' => $service->is_online_bookable,
                    ];
                });
            })
            ->toArray();
    }

    /**
     * Get service statistics
     */
    public function getServiceStatistics(int $serviceId): array
    {
        $service = $this->getService($serviceId);
        
        if (!$service) {
            return [];
        }

        // Get appointment statistics
        $appointmentStats = $service->appointments()
            ->selectRaw('
                COUNT(*) as total_appointments,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_appointments,
                COUNT(CASE WHEN status = "cancelled" THEN 1 END) as cancelled_appointments,
                COUNT(CASE WHEN status = "no_show" THEN 1 END) as no_show_appointments,
                AVG(CASE WHEN status = "completed" THEN price END) as average_price,
                SUM(CASE WHEN status = "completed" THEN price END) as total_revenue
            ')
            ->first();

        return [
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'price' => $service->price,
                'duration' => $service->default_duration_minutes,
            ],
            'statistics' => [
                'total_appointments' => $appointmentStats->total_appointments ?? 0,
                'completed_appointments' => $appointmentStats->completed_appointments ?? 0,
                'cancelled_appointments' => $appointmentStats->cancelled_appointments ?? 0,
                'no_show_appointments' => $appointmentStats->no_show_appointments ?? 0,
                'completion_rate' => $appointmentStats->total_appointments > 0 
                    ? round(($appointmentStats->completed_appointments / $appointmentStats->total_appointments) * 100, 2)
                    : 0,
                'average_price' => round($appointmentStats->average_price ?? 0, 2),
                'total_revenue' => round($appointmentStats->total_revenue ?? 0, 2),
            ],
            'staff_count' => $service->staff()->count(),
            'branch_count' => $service->branches()->count(),
        ];
    }
}