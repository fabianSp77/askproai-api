<?php

namespace App\Services\MCP;

use App\Models\PhoneNumber;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MCPCacheWarmer
{
    protected array $cacheConfig = [
        'phone_mappings' => ['ttl' => 3600, 'tags' => ['phone', 'branch']],
        'event_types' => ['ttl' => 1800, 'tags' => ['calcom', 'events']],
        'company_settings' => ['ttl' => 7200, 'tags' => ['company', 'settings']],
        'branch_services' => ['ttl' => 3600, 'tags' => ['branch', 'services']],
        'staff_availability' => ['ttl' => 900, 'tags' => ['staff', 'availability']]
    ];

    protected array $metrics = [
        'warmed' => 0,
        'failed' => 0,
        'duration' => 0
    ];

    /**
     * Warm all caches
     */
    public function warmAll(): array
    {
        $startTime = microtime(true);
        
        Log::info('[MCPCacheWarmer] Starting cache warming process');

        $this->warmPhoneMappings();
        $this->warmEventTypes();
        $this->warmCompanySettings();
        $this->warmBranchServices();
        $this->warmStaffAvailability();

        $this->metrics['duration'] = round(microtime(true) - $startTime, 2);
        
        Log::info('[MCPCacheWarmer] Cache warming completed', $this->metrics);
        
        return $this->metrics;
    }

    /**
     * Warm phone to branch mappings
     */
    protected function warmPhoneMappings(): void
    {
        try {
            $mappings = PhoneNumber::withoutGlobalScopes()
                ->with(['branch.company'])
                ->whereHas('branch', function ($query) {
                    $query->where('is_active', true);
                })
                ->get()
                ->mapWithKeys(function ($phone) {
                    return [
                        $phone->number => [
                            'branch_id' => $phone->branch_id,
                            'company_id' => $phone->branch->company_id,
                            'branch_name' => $phone->branch->name,
                            'company_name' => $phone->branch->company->name
                        ]
                    ];
                })
                ->toArray();

            Cache::tags($this->cacheConfig['phone_mappings']['tags'])
                ->put('phone_mappings:all', $mappings, $this->cacheConfig['phone_mappings']['ttl']);

            // Also cache individual mappings for faster lookups
            foreach ($mappings as $phone => $data) {
                Cache::tags($this->cacheConfig['phone_mappings']['tags'])
                    ->put("phone_mapping:{$phone}", $data, $this->cacheConfig['phone_mappings']['ttl']);
            }

            $this->metrics['warmed'] += count($mappings) + 1;
            Log::debug('[MCPCacheWarmer] Warmed phone mappings', ['count' => count($mappings)]);
        } catch (\Exception $e) {
            $this->metrics['failed']++;
            Log::error('[MCPCacheWarmer] Failed to warm phone mappings', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Warm Cal.com event types
     */
    protected function warmEventTypes(): void
    {
        try {
            // Check if calcom_event_types table exists
            if (!\Schema::hasTable('calcom_event_types')) {
                Log::warning('[MCPCacheWarmer] calcom_event_types table does not exist, skipping');
                return;
            }
            
            // For now, just cache an empty array since the model doesn't exist
            $eventTypes = [];
            
            Cache::tags($this->cacheConfig['event_types']['tags'])
                ->put('event_types:all', $eventTypes, $this->cacheConfig['event_types']['ttl']);

            $this->metrics['warmed'] += 1;
            Log::debug('[MCPCacheWarmer] Warmed event types', ['count' => 0]);
        } catch (\Exception $e) {
            $this->metrics['failed']++;
            Log::error('[MCPCacheWarmer] Failed to warm event types', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Warm company settings
     */
    protected function warmCompanySettings(): void
    {
        try {
            $companies = Company::withoutGlobalScopes()
                ->with(['branches', 'pricingModels'])
                ->where('is_active', true)
                ->get();

            foreach ($companies as $company) {
                $settings = [
                    'id' => $company->id,
                    'name' => $company->name,
                    'timezone' => $company->timezone ?? 'Europe/Berlin',
                    'language' => $company->language ?? 'de',
                    'currency' => $company->currency ?? 'EUR',
                    'business_hours' => $company->business_hours ?? [],
                    'notification_settings' => $company->notification_settings ?? [],
                    'retell_api_key' => !empty($company->retell_api_key),
                    'calcom_api_key' => !empty($company->calcom_api_key),
                    'branch_count' => $company->branches->count(),
                    'active_branches' => $company->branches->where('is_active', true)->count()
                ];

                Cache::tags($this->cacheConfig['company_settings']['tags'])
                    ->put("company_settings:{$company->id}", $settings, $this->cacheConfig['company_settings']['ttl']);

                $this->metrics['warmed']++;
            }

            Log::debug('[MCPCacheWarmer] Warmed company settings', ['count' => $companies->count()]);
        } catch (\Exception $e) {
            $this->metrics['failed']++;
            Log::error('[MCPCacheWarmer] Failed to warm company settings', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Warm branch services
     */
    protected function warmBranchServices(): void
    {
        try {
            $branches = Branch::withoutGlobalScopes()
                ->with(['services', 'staff'])
                ->where('is_active', true)
                ->get();

            foreach ($branches as $branch) {
                $services = $branch->services->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'duration' => $service->duration,
                        'price' => $service->price,
                        'is_active' => $service->is_active
                    ];
                })->toArray();

                Cache::tags($this->cacheConfig['branch_services']['tags'])
                    ->put("branch_services:{$branch->id}", $services, $this->cacheConfig['branch_services']['ttl']);

                // Also cache staff IDs for quick lookups
                $staffIds = $branch->staff->pluck('id')->toArray();
                Cache::tags(['branch', 'staff'])
                    ->put("branch_staff:{$branch->id}", $staffIds, $this->cacheConfig['branch_services']['ttl']);

                $this->metrics['warmed'] += 2;
            }

            Log::debug('[MCPCacheWarmer] Warmed branch services', ['count' => $branches->count()]);
        } catch (\Exception $e) {
            $this->metrics['failed']++;
            Log::error('[MCPCacheWarmer] Failed to warm branch services', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Warm staff availability
     */
    protected function warmStaffAvailability(): void
    {
        try {
            // Get current week's availability
            $startDate = now()->startOfWeek();
            $endDate = now()->endOfWeek();

            $availability = DB::table('staff')
                ->join('branches', 'staff.branch_id', '=', 'branches.id')
                ->where('staff.active', true)
                ->where('branches.is_active', true)
                ->select([
                    'staff.id',
                    'staff.branch_id',
                    'staff.working_hours',
                    'branches.business_hours as branch_hours'
                ])
                ->get()
                ->map(function ($staff) {
                    return [
                        'staff_id' => $staff->id,
                        'branch_id' => $staff->branch_id,
                        'working_hours' => json_decode($staff->working_hours, true) ?? [],
                        'branch_hours' => json_decode($staff->branch_hours, true) ?? []
                    ];
                })
                ->groupBy('branch_id')
                ->toArray();

            foreach ($availability as $branchId => $staffData) {
                Cache::tags($this->cacheConfig['staff_availability']['tags'])
                    ->put("staff_availability:branch:{$branchId}", $staffData, $this->cacheConfig['staff_availability']['ttl']);
                $this->metrics['warmed']++;
            }

            Log::debug('[MCPCacheWarmer] Warmed staff availability', ['branches' => count($availability)]);
        } catch (\Exception $e) {
            $this->metrics['failed']++;
            Log::error('[MCPCacheWarmer] Failed to warm staff availability', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear all warmed caches
     */
    public function clearAll(): void
    {
        foreach ($this->cacheConfig as $key => $config) {
            Cache::tags($config['tags'])->flush();
        }
        
        Log::info('[MCPCacheWarmer] All caches cleared');
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $stats = [];
        
        // Phone mappings
        $phoneMappings = Cache::tags($this->cacheConfig['phone_mappings']['tags'])
            ->get('phone_mappings:all', []);
        $stats['phone_mappings'] = count($phoneMappings);
        
        // Event types
        $eventTypes = Cache::tags($this->cacheConfig['event_types']['tags'])
            ->get('event_types:all', []);
        $stats['event_types'] = count($eventTypes);
        
        // Company settings
        try {
            $companies = Company::withoutGlobalScopes()->where('is_active', true)->count();
            $stats['company_settings'] = $companies;
        } catch (\Exception $e) {
            $stats['company_settings'] = 0;
        }
        
        // Branch services
        try {
            $branches = Branch::withoutGlobalScopes()->where('is_active', true)->count();
            $stats['branch_services'] = $branches;
        } catch (\Exception $e) {
            $stats['branch_services'] = 0;
        }
        
        return $stats;
    }
}