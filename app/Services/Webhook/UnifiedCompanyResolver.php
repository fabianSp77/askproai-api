<?php

namespace App\Services\Webhook;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use App\Services\PhoneNumberResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Unified Company Resolver for all webhook types
 * 
 * This service provides a single point of truth for resolving company context
 * from webhook payloads across all providers (Retell, Cal.com, Stripe, etc.)
 */
class UnifiedCompanyResolver
{
    protected PhoneNumberResolver $phoneResolver;
    
    // Resolution strategies in order of reliability
    const STRATEGY_METADATA = 'metadata';
    const STRATEGY_PHONE_TO = 'phone_to';
    const STRATEGY_AGENT_ID = 'agent_id';
    const STRATEGY_BOOKING_ID = 'booking_id';
    const STRATEGY_CUSTOMER_ID = 'customer_id';
    const STRATEGY_PHONE_FROM = 'phone_from';
    const STRATEGY_EMAIL = 'email';
    
    // Cache TTL in seconds
    const CACHE_TTL = 3600; // 1 hour
    
    public function __construct(PhoneNumberResolver $phoneResolver)
    {
        $this->phoneResolver = $phoneResolver;
    }
    
    /**
     * Resolve company from any webhook payload
     * 
     * @param string $provider The webhook provider (retell, calcom, stripe, etc.)
     * @param array $payload The webhook payload
     * @param array $headers Optional headers that might contain company info
     * @return array|null ['company_id' => int, 'strategy' => string, 'confidence' => float]
     */
    public function resolve(string $provider, array $payload, array $headers = []): ?array
    {
        $startTime = microtime(true);
        
        try {
            // Try provider-specific resolution first
            $result = match ($provider) {
                'retell' => $this->resolveRetellWebhook($payload, $headers),
                'calcom' => $this->resolveCalcomWebhook($payload, $headers),
                'stripe' => $this->resolveStripeWebhook($payload, $headers),
                default => $this->resolveGenericWebhook($payload, $headers)
            };
            
            if ($result) {
                $result['resolution_time'] = microtime(true) - $startTime;
                
                // Log successful resolution
                Log::info('Company resolved successfully', [
                    'provider' => $provider,
                    'company_id' => $result['company_id'],
                    'strategy' => $result['strategy'],
                    'confidence' => $result['confidence'],
                    'resolution_time' => $result['resolution_time']
                ]);
                
                return $result;
            }
            
            // Log failure
            Log::warning('Failed to resolve company from webhook', [
                'provider' => $provider,
                'payload_keys' => array_keys($payload),
                'resolution_time' => microtime(true) - $startTime
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error resolving company from webhook', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Resolve company from Retell webhook
     */
    protected function resolveRetellWebhook(array $payload, array $headers): ?array
    {
        // Extract call data
        $callData = $payload['call'] ?? $payload['data'] ?? $payload;
        
        // Strategy 1: Check metadata (highest confidence)
        if ($result = $this->resolveByMetadata($payload)) {
            return $result;
        }
        
        // Strategy 2: TO number (for incoming calls)
        $toNumber = $callData['to_number'] ?? $callData['to'] ?? null;
        if ($toNumber && $result = $this->resolveByPhoneNumber($toNumber, self::STRATEGY_PHONE_TO)) {
            return $result;
        }
        
        // Strategy 3: Agent ID
        $agentId = $callData['agent_id'] ?? $payload['agent_id'] ?? null;
        if ($agentId && $result = $this->resolveByAgentId($agentId)) {
            return $result;
        }
        
        // Strategy 4: FROM number (least reliable for new customers)
        $fromNumber = $callData['from_number'] ?? $callData['from'] ?? null;
        if ($fromNumber && $result = $this->resolveByCustomerPhone($fromNumber)) {
            return $result;
        }
        
        return null;
    }
    
    /**
     * Resolve company from Cal.com webhook
     */
    protected function resolveCalcomWebhook(array $payload, array $headers): ?array
    {
        // Strategy 1: Metadata
        if ($result = $this->resolveByMetadata($payload)) {
            return $result;
        }
        
        // Strategy 2: Booking ID/Event Type
        if (isset($payload['payload']['eventTypeId'])) {
            $eventTypeId = $payload['payload']['eventTypeId'];
            if ($result = $this->resolveByCalcomEventType($eventTypeId)) {
                return $result;
            }
        }
        
        // Strategy 3: Attendee email
        $attendees = $payload['payload']['attendees'] ?? [];
        foreach ($attendees as $attendee) {
            if (isset($attendee['email']) && $result = $this->resolveByEmail($attendee['email'])) {
                return $result;
            }
        }
        
        return null;
    }
    
    /**
     * Resolve company from Stripe webhook
     */
    protected function resolveStripeWebhook(array $payload, array $headers): ?array
    {
        // Strategy 1: Metadata
        if ($result = $this->resolveByMetadata($payload)) {
            return $result;
        }
        
        // Strategy 2: Customer ID in metadata
        $customerId = $payload['data']['object']['metadata']['customer_id'] ?? null;
        if ($customerId && $result = $this->resolveByCustomerId($customerId)) {
            return $result;
        }
        
        // Strategy 3: Email
        $email = $payload['data']['object']['customer_email'] ?? 
                 $payload['data']['object']['email'] ?? null;
        if ($email && $result = $this->resolveByEmail($email)) {
            return $result;
        }
        
        return null;
    }
    
    /**
     * Generic webhook resolution
     */
    protected function resolveGenericWebhook(array $payload, array $headers): ?array
    {
        // Try all strategies in order of reliability
        
        // 1. Metadata
        if ($result = $this->resolveByMetadata($payload)) {
            return $result;
        }
        
        // 2. Phone numbers
        $phoneFields = ['phone', 'phone_number', 'to_number', 'to', 'from_number', 'from'];
        foreach ($phoneFields as $field) {
            if (isset($payload[$field]) && $result = $this->resolveByPhoneNumber($payload[$field])) {
                return $result;
            }
        }
        
        // 3. Email
        $emailFields = ['email', 'customer_email', 'user_email'];
        foreach ($emailFields as $field) {
            if (isset($payload[$field]) && $result = $this->resolveByEmail($payload[$field])) {
                return $result;
            }
        }
        
        return null;
    }
    
    /**
     * Resolve by metadata (most reliable)
     */
    protected function resolveByMetadata(array $payload): ?array
    {
        $companyId = $payload['metadata']['company_id'] ?? 
                     $payload['meta']['company_id'] ?? 
                     null;
        
        if (!$companyId) {
            return null;
        }
        
        // Verify company exists and is active
        $company = Company::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('id', $companyId)
            ->where('is_active', true)
            ->first();
            
        if (!$company) {
            Log::warning('Invalid company_id in webhook metadata', [
                'claimed_company_id' => $companyId
            ]);
            return null;
        }
        
        return [
            'company_id' => $company->id,
            'strategy' => self::STRATEGY_METADATA,
            'confidence' => 1.0 // Highest confidence
        ];
    }
    
    /**
     * Resolve by phone number
     */
    protected function resolveByPhoneNumber(string $phoneNumber, string $strategy = null): ?array
    {
        $cleanNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        if (empty($cleanNumber)) {
            return null;
        }
        
        // Check cache
        $cacheKey = "unified_phone_company:{$cleanNumber}";
        if ($cached = Cache::get($cacheKey)) {
            return array_merge($cached, ['from_cache' => true]);
        }
        
        // Use PhoneNumberResolver
        $resolution = $this->phoneResolver->resolve($cleanNumber);
        if ($resolution['found'] && isset($resolution['branch_id'])) {
            $branch = Branch::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                ->with('company')
                ->find($resolution['branch_id']);
                
            if ($branch && $branch->is_active && $branch->company && $branch->company->is_active) {
                $result = [
                    'company_id' => $branch->company_id,
                    'strategy' => $strategy ?? self::STRATEGY_PHONE_TO,
                    'confidence' => 0.9
                ];
                
                Cache::put($cacheKey, $result, self::CACHE_TTL);
                return $result;
            }
        }
        
        return null;
    }
    
    /**
     * Resolve by agent ID
     */
    protected function resolveByAgentId(string $agentId): ?array
    {
        if (empty($agentId)) {
            return null;
        }
        
        // Check cache
        $cacheKey = "unified_agent_company:{$agentId}";
        if ($cached = Cache::get($cacheKey)) {
            return array_merge($cached, ['from_cache' => true]);
        }
        
        // Check phone_numbers table
        $phoneNumber = PhoneNumber::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('retell_agent_id', $agentId)
            ->where('is_active', true)
            ->with(['branch.company'])
            ->first();
            
        if ($phoneNumber && $phoneNumber->branch && $phoneNumber->branch->company) {
            if ($phoneNumber->branch->is_active && $phoneNumber->branch->company->is_active) {
                $result = [
                    'company_id' => $phoneNumber->branch->company_id,
                    'strategy' => self::STRATEGY_AGENT_ID,
                    'confidence' => 0.95
                ];
                
                Cache::put($cacheKey, $result, self::CACHE_TTL);
                return $result;
            }
        }
        
        // Check retell_agents table
        $agent = RetellAgent::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('agent_id', $agentId)
            ->where('active', true)
            ->first();
            
        if ($agent && $agent->company_id) {
            $company = Company::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
                ->where('id', $agent->company_id)
                ->where('is_active', true)
                ->first();
                
            if ($company) {
                $result = [
                    'company_id' => $company->id,
                    'strategy' => self::STRATEGY_AGENT_ID,
                    'confidence' => 0.9
                ];
                
                Cache::put($cacheKey, $result, self::CACHE_TTL);
                return $result;
            }
        }
        
        return null;
    }
    
    /**
     * Resolve by customer phone (least reliable)
     */
    protected function resolveByCustomerPhone(string $phoneNumber): ?array
    {
        $cleanNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        if (empty($cleanNumber)) {
            return null;
        }
        
        // Find most recent customer
        $customer = Customer::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('phone', $cleanNumber)
            ->whereHas('company', function ($q) {
                $q->where('is_active', true);
            })
            ->orderBy('updated_at', 'desc')
            ->first();
            
        if ($customer && $customer->company_id) {
            return [
                'company_id' => $customer->company_id,
                'strategy' => self::STRATEGY_PHONE_FROM,
                'confidence' => 0.6 // Lower confidence
            ];
        }
        
        return null;
    }
    
    /**
     * Resolve by customer ID
     */
    protected function resolveByCustomerId($customerId): ?array
    {
        if (empty($customerId)) {
            return null;
        }
        
        $customer = Customer::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('id', $customerId)
            ->whereHas('company', function ($q) {
                $q->where('is_active', true);
            })
            ->first();
            
        if ($customer && $customer->company_id) {
            return [
                'company_id' => $customer->company_id,
                'strategy' => self::STRATEGY_CUSTOMER_ID,
                'confidence' => 0.85
            ];
        }
        
        return null;
    }
    
    /**
     * Resolve by email
     */
    protected function resolveByEmail(string $email): ?array
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        
        // Check customers
        $customer = Customer::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('email', $email)
            ->whereHas('company', function ($q) {
                $q->where('is_active', true);
            })
            ->orderBy('updated_at', 'desc')
            ->first();
            
        if ($customer && $customer->company_id) {
            return [
                'company_id' => $customer->company_id,
                'strategy' => self::STRATEGY_EMAIL,
                'confidence' => 0.7
            ];
        }
        
        return null;
    }
    
    /**
     * Resolve by Cal.com event type
     */
    protected function resolveByCalcomEventType($eventTypeId): ?array
    {
        if (empty($eventTypeId)) {
            return null;
        }
        
        // Cache lookup
        $cacheKey = "unified_calcom_event_company:{$eventTypeId}";
        if ($cached = Cache::get($cacheKey)) {
            return array_merge($cached, ['from_cache' => true]);
        }
        
        // Find event type
        $eventType = \App\Models\CalcomEventType::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
            ->where('calcom_event_type_id', $eventTypeId)
            ->whereHas('company', function ($q) {
                $q->where('is_active', true);
            })
            ->first();
            
        if ($eventType && $eventType->company_id) {
            $result = [
                'company_id' => $eventType->company_id,
                'strategy' => self::STRATEGY_BOOKING_ID,
                'confidence' => 0.9
            ];
            
            Cache::put($cacheKey, $result, self::CACHE_TTL);
            return $result;
        }
        
        return null;
    }
    
    /**
     * Clear all resolution caches
     */
    public function clearCaches(): void
    {
        $patterns = [
            'unified_phone_company:*',
            'unified_agent_company:*',
            'unified_calcom_event_company:*'
        ];
        
        foreach ($patterns as $pattern) {
            $keys = Cache::getRedis()->keys($pattern);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }
        
        Log::info('Unified company resolver caches cleared');
    }
    
    /**
     * Get resolution statistics
     */
    public function getStats(): array
    {
        // This could be enhanced to track resolution success rates
        // by strategy, provider, etc.
        return [
            'strategies' => [
                self::STRATEGY_METADATA => ['confidence' => 1.0, 'description' => 'Verified metadata'],
                self::STRATEGY_PHONE_TO => ['confidence' => 0.9, 'description' => 'Phone number mapping'],
                self::STRATEGY_AGENT_ID => ['confidence' => 0.95, 'description' => 'Agent configuration'],
                self::STRATEGY_BOOKING_ID => ['confidence' => 0.9, 'description' => 'Booking/Event mapping'],
                self::STRATEGY_CUSTOMER_ID => ['confidence' => 0.85, 'description' => 'Customer record'],
                self::STRATEGY_PHONE_FROM => ['confidence' => 0.6, 'description' => 'Customer phone (unreliable)'],
                self::STRATEGY_EMAIL => ['confidence' => 0.7, 'description' => 'Customer email']
            ],
            'cache_enabled' => true,
            'cache_ttl' => self::CACHE_TTL
        ];
    }
}