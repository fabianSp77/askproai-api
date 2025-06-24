<?php

namespace App\Services\MCP;

use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Services\PhoneNumberResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Helpers\SafeQueryHelper;

class MCPContextResolver
{
    protected PhoneNumberResolver $phoneResolver;
    protected array $config;
    
    public function __construct(PhoneNumberResolver $phoneResolver)
    {
        $this->phoneResolver = $phoneResolver;
        $this->config = [
            'cache' => [
                'ttl' => 300, // 5 minutes
                'prefix' => 'mcp:context'
            ],
            'fallback' => [
                'use_default_company' => false,
                'default_company_id' => null
            ]
        ];
    }
    
    /**
     * Resolve context from phone number
     */
    public function resolveFromPhone(string $phoneNumber): array
    {
        $cacheKey = $this->getCacheKey('phone', $phoneNumber);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($phoneNumber) {
            try {
                // Use PhoneNumberResolver for primary resolution
                $result = $this->phoneResolver->resolve($phoneNumber);
                
                if (!$result['found']) {
                    Log::warning('MCP: Phone number not found', [
                        'phone' => $phoneNumber,
                        'normalized' => $result['normalized_number'] ?? null
                    ]);
                    
                    return $this->handleNotFound($phoneNumber);
                }
                
                // Get branch and company details
                $branch = Branch::with(['company', 'services', 'staff'])
                    ->find($result['branch_id']);
                
                if (!$branch) {
                    Log::error('MCP: Branch not found', [
                        'branch_id' => $result['branch_id'],
                        'phone' => $phoneNumber
                    ]);
                    
                    return $this->handleNotFound($phoneNumber);
                }
                
                // Build comprehensive context
                return [
                    'success' => true,
                    'phone_number' => $phoneNumber,
                    'normalized_number' => $result['normalized_number'],
                    'company' => [
                        'id' => $branch->company->id,
                        'name' => $branch->company->name,
                        'is_active' => $branch->company->is_active,
                        'settings' => $this->getCompanySettings($branch->company),
                    ],
                    'branch' => [
                        'id' => $branch->id,
                        'uuid' => $branch->uuid,
                        'name' => $branch->name,
                        'is_active' => $branch->is_active,
                        'calcom_event_type_id' => $branch->calcom_event_type_id,
                        'retell_agent_id' => $branch->retell_agent_id,
                        'timezone' => $branch->timezone ?? 'Europe/Berlin',
                        'business_hours' => $branch->business_hours,
                    ],
                    'services' => $branch->services->map(function ($service) {
                        return [
                            'id' => $service->id,
                            'name' => $service->name,
                            'duration' => $service->duration,
                            'price' => $service->price,
                            'calcom_event_type_id' => $service->pivot->calcom_event_type_id ?? null,
                        ];
                    })->toArray(),
                    'staff' => $branch->staff->map(function ($staff) {
                        return [
                            'id' => $staff->id,
                            'name' => $staff->name,
                            'email' => $staff->email,
                            'is_active' => $staff->is_active,
                            'calcom_user_id' => $staff->calcom_user_id,
                        ];
                    })->toArray(),
                    'resolved_at' => now()->toIso8601String(),
                ];
                
            } catch (\Exception $e) {
                Log::error('MCP: Context resolution failed', [
                    'phone' => $phoneNumber,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Context resolution failed',
                    'message' => $e->getMessage(),
                    'phone_number' => $phoneNumber,
                ];
            }
        });
    }
    
    /**
     * Resolve context from company ID
     */
    public function resolveFromCompany(string $companyId, string $branchId = null): array
    {
        $cacheKey = $this->getCacheKey('company', $companyId . ($branchId ? ":$branchId" : ''));
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($companyId, $branchId) {
            try {
                $company = Company::with(['branches.services', 'branches.staff'])
                    ->find($companyId);
                
                if (!$company) {
                    return [
                        'success' => false,
                        'error' => 'Company not found',
                        'company_id' => $companyId,
                    ];
                }
                
                // If branch ID provided, get specific branch
                if ($branchId) {
                    $branch = $company->branches()->find($branchId);
                    if (!$branch) {
                        return [
                            'success' => false,
                            'error' => 'Branch not found',
                            'company_id' => $companyId,
                            'branch_id' => $branchId,
                        ];
                    }
                    
                    $branches = collect([$branch]);
                } else {
                    $branches = $company->branches;
                }
                
                return [
                    'success' => true,
                    'company' => [
                        'id' => $company->id,
                        'name' => $company->name,
                        'is_active' => $company->is_active,
                        'settings' => $this->getCompanySettings($company),
                    ],
                    'branches' => $branches->map(function ($branch) {
                        return [
                            'id' => $branch->id,
                            'uuid' => $branch->uuid,
                            'name' => $branch->name,
                            'is_active' => $branch->is_active,
                            'calcom_event_type_id' => $branch->calcom_event_type_id,
                            'retell_agent_id' => $branch->retell_agent_id,
                            'phone_numbers' => $branch->phoneNumbers->pluck('phone_number')->toArray(),
                            'services' => $branch->services->map(function ($service) {
                                return [
                                    'id' => $service->id,
                                    'name' => $service->name,
                                    'duration' => $service->duration,
                                    'price' => $service->price,
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                    'resolved_at' => now()->toIso8601String(),
                ];
                
            } catch (\Exception $e) {
                Log::error('MCP: Company context resolution failed', [
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'error' => $e->getMessage()
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Context resolution failed',
                    'message' => $e->getMessage(),
                    'company_id' => $companyId,
                ];
            }
        });
    }
    
    /**
     * Set tenant context for multi-tenant operations
     */
    public function setTenantContext(string $companyId): bool
    {
        try {
            // Store in request context
            request()->attributes->set('company_id', $companyId);
            
            // Set for current session
            session(['company_id' => $companyId]);
            
            // Clear any cached queries for other tenants
            $this->clearTenantCache();
            
            Log::info('MCP: Tenant context set', [
                'company_id' => $companyId
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('MCP: Failed to set tenant context', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Get current tenant context
     */
    public function getCurrentContext(): ?array
    {
        $companyId = request()->attributes->get('company_id') ?? session('company_id');
        
        if (!$companyId) {
            return null;
        }
        
        return $this->resolveFromCompany($companyId);
    }
    
    /**
     * Clear tenant context
     */
    public function clearTenantContext(): void
    {
        request()->attributes->remove('company_id');
        session()->forget('company_id');
        $this->clearTenantCache();
    }
    
    /**
     * Handle not found scenarios
     */
    protected function handleNotFound(string $phoneNumber): array
    {
        // Check if we should use fallback
        if ($this->config['fallback']['use_default_company'] && $this->config['fallback']['default_company_id']) {
            $defaultContext = $this->resolveFromCompany($this->config['fallback']['default_company_id']);
            
            if ($defaultContext['success']) {
                $defaultContext['fallback_used'] = true;
                $defaultContext['original_phone'] = $phoneNumber;
                return $defaultContext;
            }
        }
        
        return [
            'success' => false,
            'error' => 'Phone number not associated with any branch',
            'phone_number' => $phoneNumber,
            'suggestions' => $this->getSuggestions($phoneNumber),
        ];
    }
    
    /**
     * Get suggestions for unmatched phone numbers
     */
    protected function getSuggestions(string $phoneNumber): array
    {
        // Extract area code or prefix
        $prefix = substr($phoneNumber, 0, 6);
        
        // Find branches with similar phone numbers
        $similarPhones = PhoneNumber::where('is_active', true)
            ->where(function($q) use ($prefix) {
                SafeQueryHelper::whereLike($q, 'phone_number', $prefix, 'right');
            })
            ->with('branch.company')
            ->limit(3)
            ->get();
        
        return $similarPhones->map(function ($phone) {
            return [
                'phone' => $phone->phone_number,
                'branch' => $phone->branch->name,
                'company' => $phone->branch->company->name,
            ];
        })->toArray();
    }
    
    /**
     * Get company settings
     */
    protected function getCompanySettings(Company $company): array
    {
        return [
            'language' => $company->language ?? 'de',
            'timezone' => $company->timezone ?? 'Europe/Berlin',
            'business_hours' => $company->business_hours ?? [],
            'booking_buffer' => $company->booking_buffer ?? 15,
            'cancellation_policy' => $company->cancellation_policy ?? 24,
            'max_advance_booking' => $company->max_advance_booking ?? 30,
            'reminder_enabled' => $company->reminder_enabled ?? true,
            'webhook_endpoints' => $company->webhook_endpoints ?? [],
        ];
    }
    
    /**
     * Clear tenant-specific cache
     */
    protected function clearTenantCache(): void
    {
        // Clear query cache for models
        Cache::tags(['tenant:' . (request()->attributes->get('company_id') ?? 'unknown')])->flush();
    }
    
    /**
     * Generate cache key
     */
    protected function getCacheKey(string $type, string $identifier): string
    {
        return sprintf(
            '%s:%s:%s',
            $this->config['cache']['prefix'],
            $type,
            md5($identifier)
        );
    }
    
    /**
     * Invalidate context cache
     */
    public function invalidateCache(string $type = null, string $identifier = null): void
    {
        if ($type && $identifier) {
            Cache::forget($this->getCacheKey($type, $identifier));
        } else {
            // Clear all context cache
            Cache::flush();
        }
    }
}