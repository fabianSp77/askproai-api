<?php

namespace App\Services\Setup;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class CachedCompanyLoader
{
    private const CACHE_KEY = 'wizard.companies.active';
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get active companies with caching
     */
    public function getActiveCompanies(int $limit = 100): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () use ($limit) {
            return Company::select('id', 'name', 'industry')
                ->where('is_active', true)
                ->orderBy('name')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Clear the cache when companies are updated
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get company with branches efficiently
     */
    public function getCompanyWithBranches(int $companyId): ?Company
    {
        $cacheKey = "wizard.company.{$companyId}.branches";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId) {
            return Company::with([
                'branches' => function ($query) {
                    $query->select('id', 'company_id', 'name', 'city', 'phone_number', 'is_active')
                        ->where('is_active', true);
                }
            ])->find($companyId);
        });
    }
}