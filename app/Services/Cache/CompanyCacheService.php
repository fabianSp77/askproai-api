<?php

namespace App\Services\Cache;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Specialized cache service for company-related data
 * Implements aggressive caching for rarely-changing data
 */
class CompanyCacheService
{
    private CacheManager $cacheManager;
    
    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }
    
    /**
     * Get company with all relations cached
     */
    public function getCompanyWithRelations(int $companyId): ?Company
    {
        return $this->cacheManager->remember(
            "company:full:{$companyId}",
            3600, // 1 hour
            function() use ($companyId) {
                return Company::with([
                    'branches' => function($q) {
                        $q->where('is_active', true)
                          ->select('id', 'company_id', 'name', 'phone', 'address', 'is_active');
                    },
                    'branches.staff' => function($q) {
                        $q->where('is_active', true)
                          ->select('id', 'branch_id', 'name', 'email', 'phone');
                    },
                    'services' => function($q) {
                        $q->where('is_active', true)
                          ->select('id', 'company_id', 'name', 'duration', 'price');
                    }
                ])
                ->find($companyId);
            }
        );
    }
    
    /**
     * Get company by phone number (cached)
     */
    public function getCompanyByPhone(string $phoneNumber): ?Company
    {
        return $this->cacheManager->remember(
            "company:phone:{$phoneNumber}",
            3600,
            function() use ($phoneNumber) {
                // First check branches
                $branch = Branch::where('phone', $phoneNumber)
                    ->orWhere('phone_numbers', 'LIKE', "%{$phoneNumber}%")
                    ->first();
                
                if ($branch) {
                    return $branch->company;
                }
                
                // Then check company
                return Company::where('phone_number', $phoneNumber)->first();
            }
        );
    }
    
    /**
     * Get active branches for company
     */
    public function getActiveBranches(int $companyId): Collection
    {
        return $this->cacheManager->remember(
            "company:{$companyId}:branches:active",
            1800, // 30 minutes
            function() use ($companyId) {
                return Branch::where('company_id', $companyId)
                    ->where('is_active', true)
                    ->with(['workingHours'])
                    ->get();
            }
        );
    }
    
    /**
     * Get branch by phone number
     */
    public function getBranchByPhone(string $phoneNumber): ?Branch
    {
        return $this->cacheManager->remember(
            "branch:phone:{$phoneNumber}",
            3600,
            function() use ($phoneNumber) {
                return Branch::where('phone', $phoneNumber)
                    ->orWhere('phone_numbers', 'LIKE', "%{$phoneNumber}%")
                    ->with(['company:id,name,settings'])
                    ->first();
            }
        );
    }
    
    /**
     * Get available staff for branch and service
     */
    public function getAvailableStaff(int $branchId, int $serviceId): Collection
    {
        $cacheKey = "branch:{$branchId}:service:{$serviceId}:staff";
        
        return $this->cacheManager->remember(
            $cacheKey,
            600, // 10 minutes
            function() use ($branchId, $serviceId) {
                return Staff::where('branch_id', $branchId)
                    ->where('is_active', true)
                    ->whereHas('services', function($q) use ($serviceId) {
                        $q->where('services.id', $serviceId);
                    })
                    ->select('id', 'name', 'branch_id')
                    ->get();
            }
        );
    }
    
    /**
     * Get company settings (heavily cached)
     */
    public function getCompanySettings(int $companyId): array
    {
        return $this->cacheManager->remember(
            "company:{$companyId}:settings",
            7200, // 2 hours
            function() use ($companyId) {
                $company = Company::find($companyId);
                
                return [
                    'timezone' => $company->timezone ?? 'Europe/Berlin',
                    'language' => $company->language ?? 'de',
                    'currency' => $company->currency ?? 'EUR',
                    'booking_buffer_minutes' => $company->booking_buffer_minutes ?? 0,
                    'cancellation_policy_hours' => $company->cancellation_policy_hours ?? 24,
                    'max_advance_booking_days' => $company->max_advance_booking_days ?? 90,
                    'require_email_confirmation' => $company->require_email_confirmation ?? true,
                    'allow_online_booking' => $company->allow_online_booking ?? true,
                ];
            }
        );
    }
    
    /**
     * Invalidate all caches for a company
     */
    public function invalidateCompanyCache(int $companyId): void
    {
        $patterns = [
            "company:full:{$companyId}",
            "company:{$companyId}:*",
            "branch:*:company:{$companyId}",
        ];
        
        foreach ($patterns as $pattern) {
            $this->cacheManager->forgetByPattern($pattern);
        }
        
        // Also invalidate tagged caches
        Cache::tags(['company', "company-{$companyId}"])->flush();
    }
    
    /**
     * Warm critical caches for a company
     */
    public function warmCompanyCaches(int $companyId): void
    {
        $warmups = [
            [
                'key' => "company:full:{$companyId}",
                'callback' => fn() => $this->getCompanyWithRelations($companyId),
                'ttl' => 3600
            ],
            [
                'key' => "company:{$companyId}:settings",
                'callback' => fn() => $this->getCompanySettings($companyId),
                'ttl' => 7200
            ],
            [
                'key' => "company:{$companyId}:branches:active",
                'callback' => fn() => $this->getActiveBranches($companyId),
                'ttl' => 1800
            ],
        ];
        
        $this->cacheManager->warm($warmups);
    }
    
    /**
     * Get cache keys for monitoring
     */
    public function getCacheKeys(int $companyId): array
    {
        return [
            "company:full:{$companyId}" => Cache::has("company:full:{$companyId}"),
            "company:{$companyId}:settings" => Cache::has("company:{$companyId}:settings"),
            "company:{$companyId}:branches:active" => Cache::has("company:{$companyId}:branches:active"),
        ];
    }
}