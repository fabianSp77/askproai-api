<?php

namespace App\Services\Webhook;

use App\Models\Branch;
use App\Models\Company;
use App\Models\PhoneNumber;
use App\Services\PhoneNumberResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WebhookCompanyResolver
{
    protected PhoneNumberResolver $phoneResolver;
    
    public function __construct(PhoneNumberResolver $phoneResolver)
    {
        $this->phoneResolver = $phoneResolver;
    }
    
    /**
     * Resolve company ID from webhook payload
     * 
     * SECURITY: This method MUST correctly identify the company or reject the webhook
     * Never default to a random company - that would be a critical security breach
     */
    public function resolveFromWebhook(array $payload): ?int
    {
        $startTime = microtime(true);
        $strategies = [];
        
        // Strategy 1: Check metadata for company_id (if signed/verified)
        if (isset($payload['metadata']['company_id'])) {
            $companyId = (int) $payload['metadata']['company_id'];
            
            // Verify this company actually exists and is active
            $company = Company::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                ->where('id', $companyId)
                ->where('is_active', true)
                ->first();
                
            if ($company) {
                $strategies[] = 'metadata';
                Log::info('Company resolved from metadata', [
                    'company_id' => $companyId,
                    'resolution_time' => microtime(true) - $startTime
                ]);
                return $companyId;
            } else {
                Log::warning('Invalid company_id in webhook metadata', [
                    'claimed_company_id' => $companyId,
                    'payload' => $payload
                ]);
            }
        }
        
        // Extract call data from various payload structures
        $callData = $payload['call'] ?? $payload['data'] ?? $payload;
        
        // Get phone numbers from various possible locations
        $toNumber = $callData['to_number'] ?? 
                    $callData['to'] ?? 
                    $payload['to_number'] ?? 
                    null;
                    
        $fromNumber = $callData['from_number'] ?? 
                      $callData['from'] ?? 
                      $payload['from_number'] ?? 
                      null;
        
        // Strategy 2: Resolve by TO number (incoming calls to our number)
        if ($toNumber) {
            $companyId = $this->resolveByPhoneNumber($toNumber);
            if ($companyId) {
                $strategies[] = 'to_number';
                Log::info('Company resolved from TO number', [
                    'to_number' => $toNumber,
                    'company_id' => $companyId,
                    'resolution_time' => microtime(true) - $startTime
                ]);
                return $companyId;
            }
        }
        
        // Strategy 3: Check agent_id mapping (more reliable than customer phone)
        $agentId = $callData['agent_id'] ?? $payload['agent_id'] ?? null;
        if ($agentId) {
            $companyId = $this->resolveByAgentId($agentId);
            if ($companyId) {
                $strategies[] = 'agent_id';
                Log::info('Company resolved from agent ID', [
                    'agent_id' => $agentId,
                    'company_id' => $companyId,
                    'resolution_time' => microtime(true) - $startTime
                ]);
                return $companyId;
            }
        }
        
        // Strategy 4: Resolve by FROM number (for known customers) - LEAST RELIABLE
        if ($fromNumber) {
            $companyId = $this->resolveByCustomerPhone($fromNumber);
            if ($companyId) {
                $strategies[] = 'customer_phone';
                Log::info('Company resolved from customer phone', [
                    'from_number' => $fromNumber,
                    'company_id' => $companyId,
                    'resolution_time' => microtime(true) - $startTime
                ]);
                return $companyId;
            }
        }
        
        // NO FALLBACK TO RANDOM COMPANY!
        // Log the failure for investigation
        Log::error('CRITICAL: Unable to resolve company for webhook', [
            'to_number' => $toNumber,
            'from_number' => $fromNumber,
            'agent_id' => $agentId,
            'strategies_tried' => $strategies,
            'payload_keys' => array_keys($payload),
            'resolution_time' => microtime(true) - $startTime,
            'webhook_type' => $payload['event_type'] ?? 'unknown'
        ]);
        
        // Notify administrators of resolution failure
        $this->notifyResolutionFailure($payload, $strategies);
        
        // Return null - the webhook handler should reject this webhook
        return null;
    }
    
    /**
     * Resolve company by phone number with validation
     */
    protected function resolveByPhoneNumber(string $phoneNumber): ?int
    {
        // Clean phone number
        $cleanNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        if (empty($cleanNumber)) {
            return null;
        }
        
        // Check cache first
        $cacheKey = "phone_company_map:{$cleanNumber}";
        $cachedCompanyId = Cache::get($cacheKey);
        if ($cachedCompanyId !== null) {
            // Verify cached company still exists and is active
            $exists = Company::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                ->where('id', $cachedCompanyId)
                ->where('is_active', true)
                ->exists();
            
            if ($exists) {
                return $cachedCompanyId;
            } else {
                Cache::forget($cacheKey);
            }
        }
        
        // Try PhoneNumberResolver service
        $resolution = $this->phoneResolver->resolve($cleanNumber);
        if ($resolution['found'] && isset($resolution['branch_id'])) {
            // Get branch to find company without tenant scope
            $branch = Branch::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                ->where('id', $resolution['branch_id'])
                ->where('is_active', true)
                ->first();
                
            if ($branch && $branch->company_id) {
                // Verify company is active
                $company = Company::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                    ->where('id', $branch->company_id)
                    ->where('is_active', true)
                    ->first();
                    
                if ($company) {
                    Cache::put($cacheKey, $company->id, 3600); // Cache for 1 hour
                    return $company->id;
                }
            }
        }
        
        // Direct lookup in phone_numbers table without tenant scope
        $phoneRecord = PhoneNumber::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('number', $cleanNumber)
            ->where('is_active', true)
            ->first();
            
        if ($phoneRecord && $phoneRecord->branch) {
            $branch = $phoneRecord->branch;
            if ($branch->is_active && $branch->company) {
                if ($branch->company->is_active) {
                    Cache::put($cacheKey, $branch->company_id, 3600);
                    return $branch->company_id;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Resolve company by customer phone number
     * 
     * WARNING: This is the least reliable method as customers can belong to multiple companies
     */
    protected function resolveByCustomerPhone(string $phoneNumber): ?int
    {
        $cleanNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        if (empty($cleanNumber)) {
            return null;
        }
        
        // Look for existing customer without tenant scope
        // Get the most recently active customer with this phone
        $customer = \App\Models\Customer::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('phone', $cleanNumber)
            ->whereHas('company', function ($q) {
                $q->where('is_active', true);
            })
            ->orderBy('updated_at', 'desc')
            ->first();
            
        if ($customer && $customer->company_id) {
            // Log this as it's not 100% reliable
            Log::info('Company resolved by customer phone (least reliable method)', [
                'customer_id' => $customer->id,
                'company_id' => $customer->company_id,
                'phone' => $cleanNumber
            ]);
            
            return $customer->company_id;
        }
        
        return null;
    }
    
    /**
     * Resolve company by Retell agent ID
     */
    protected function resolveByAgentId(string $agentId): ?int
    {
        if (empty($agentId)) {
            return null;
        }
        
        // Check cache
        $cacheKey = "agent_company_map:{$agentId}";
        $cachedCompanyId = Cache::get($cacheKey);
        if ($cachedCompanyId !== null) {
            // Verify cached company still exists and is active
            $exists = Company::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                ->where('id', $cachedCompanyId)
                ->where('is_active', true)
                ->exists();
                
            if ($exists) {
                return $cachedCompanyId;
            } else {
                Cache::forget($cacheKey);
            }
        }
        
        // Look up in phone_numbers table for agent mapping
        $phoneNumber = PhoneNumber::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('retell_agent_id', $agentId)
            ->where('is_active', true)
            ->whereHas('branch', function ($q) {
                $q->where('is_active', true)
                  ->whereHas('company', function ($q2) {
                      $q2->where('is_active', true);
                  });
            })
            ->first();
            
        if ($phoneNumber && $phoneNumber->branch && $phoneNumber->branch->company_id) {
            Cache::put($cacheKey, $phoneNumber->branch->company_id, 3600);
            return $phoneNumber->branch->company_id;
        }
        
        // Check companies table for default agent
        $company = Company::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('retell_agent_id', $agentId)
            ->where('is_active', true)
            ->first();
            
        if ($company) {
            Cache::put($cacheKey, $company->id, 3600);
            return $company->id;
        }
        
        return null;
    }
    
    /**
     * Clear resolution caches
     */
    public function clearCaches(): void
    {
        // Clear all phone number mappings
        $phoneKeys = Cache::get('phone_company_map_keys', []);
        foreach ($phoneKeys as $key) {
            Cache::forget($key);
        }
        Cache::forget('phone_company_map_keys');
        
        // Clear all agent mappings
        $agentKeys = Cache::get('agent_company_map_keys', []);
        foreach ($agentKeys as $key) {
            Cache::forget($key);
        }
        Cache::forget('agent_company_map_keys');
    }
    
    /**
     * Notify administrators of webhook resolution failure
     */
    protected function notifyResolutionFailure(array $payload, array $strategiesTried): void
    {
        // Log to a dedicated webhook failures log
        Log::channel('webhook_failures')->error('Failed to resolve company for webhook', [
            'payload' => $payload,
            'strategies_tried' => $strategiesTried,
            'timestamp' => now()->toIso8601String()
        ]);
        
        // TODO: Send email/Slack notification to administrators
        // This could indicate:
        // 1. Misconfigured phone numbers
        // 2. Missing agent mappings
        // 3. Potential security probe
    }
}