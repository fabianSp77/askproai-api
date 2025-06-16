<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Company;
use App\Services\CacheService;
use App\Services\CalcomService;
use App\Services\CompanyService;
use App\Services\StaffService;
use App\Services\ServiceService;
use Illuminate\Support\Facades\Log;

class WarmCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $companyId;
    protected $cacheType;

    /**
     * Create a new job instance.
     *
     * @param int $companyId
     * @param string|null $cacheType Specific cache type to warm, or null for all
     */
    public function __construct(int $companyId, ?string $cacheType = null)
    {
        $this->companyId = $companyId;
        $this->cacheType = $cacheType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $company = Company::find($this->companyId);
        
        if (!$company || !$company->is_active) {
            Log::warning('Cache warming skipped for inactive or missing company', [
                'company_id' => $this->companyId
            ]);
            return;
        }

        Log::info('Starting cache warming', [
            'company_id' => $this->companyId,
            'cache_type' => $this->cacheType ?? 'all'
        ]);

        try {
            switch ($this->cacheType) {
                case 'event_types':
                    $this->warmEventTypesCache();
                    break;
                case 'company_settings':
                    $this->warmCompanySettingsCache();
                    break;
                case 'staff_schedules':
                    $this->warmStaffSchedulesCache();
                    break;
                case 'services':
                    $this->warmServicesCache();
                    break;
                default:
                    // Warm all caches
                    $this->warmEventTypesCache();
                    $this->warmCompanySettingsCache();
                    $this->warmStaffSchedulesCache();
                    $this->warmServicesCache();
            }

            Log::info('Cache warming completed', [
                'company_id' => $this->companyId,
                'cache_type' => $this->cacheType ?? 'all'
            ]);
        } catch (\Exception $e) {
            Log::error('Cache warming failed', [
                'company_id' => $this->companyId,
                'cache_type' => $this->cacheType ?? 'all',
                'error' => $e->getMessage()
            ]);
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Warm Cal.com event types cache
     */
    protected function warmEventTypesCache(): void
    {
        $calcomService = app(CalcomService::class);
        $calcomService->getEventTypes($this->companyId);
        
        Log::debug('Warmed event types cache', ['company_id' => $this->companyId]);
    }

    /**
     * Warm company settings cache
     */
    protected function warmCompanySettingsCache(): void
    {
        $companyService = app(CompanyService::class);
        $companyService->getSettings($this->companyId);
        
        Log::debug('Warmed company settings cache', ['company_id' => $this->companyId]);
    }

    /**
     * Warm staff schedules cache
     */
    protected function warmStaffSchedulesCache(): void
    {
        $staffService = app(StaffService::class);
        $company = Company::with('staff')->find($this->companyId);
        
        foreach ($company->staff as $staff) {
            if ($staff->active && $staff->is_bookable) {
                $staffService->getSchedule($staff->id);
                
                // Also warm weekly schedule for current week
                $staffService->getWeeklySchedule($staff->id);
            }
        }
        
        Log::debug('Warmed staff schedules cache', [
            'company_id' => $this->companyId,
            'staff_count' => $company->staff->count()
        ]);
    }

    /**
     * Warm services cache
     */
    protected function warmServicesCache(): void
    {
        $serviceService = app(ServiceService::class);
        $company = Company::with('branches')->find($this->companyId);
        
        // Warm company-wide services
        $serviceService->getServicesList($this->companyId);
        
        // Warm branch-specific services
        foreach ($company->branches as $branch) {
            $serviceService->getServicesList($this->companyId, $branch->id);
        }
        
        Log::debug('Warmed services cache', [
            'company_id' => $this->companyId,
            'branch_count' => $company->branches->count()
        ]);
    }

    /**
     * The job's retry delay in seconds
     */
    public function retryAfter(): int
    {
        return 60; // Retry after 1 minute
    }

    /**
     * Determine the number of times the job may be attempted
     */
    public function tries(): int
    {
        return 3;
    }
}