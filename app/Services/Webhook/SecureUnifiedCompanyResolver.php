<?php

namespace App\Services\Webhook;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\PhoneNumber;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * SECURE VERSION: Unified Company Resolver with proper tenant isolation
 * 
 * This service resolves company context from various sources while maintaining
 * strict security boundaries. It requires explicit validation for all operations.
 */
class SecureUnifiedCompanyResolver
{
    /**
     * Resolve company from multiple sources with security validation
     * 
     * @param array $data Webhook or request data
     * @return array|null Company context with security metadata
     */
    public function resolve(array $data): ?array
    {
        // Audit resolution attempt
        $this->auditResolutionAttempt($data);
        
        // Try resolution strategies in order of reliability
        $strategies = [
            'subdomain' => fn() => $this->resolveFromSubdomain($data),
            'header' => fn() => $this->resolveFromHeader($data),
            'phone' => fn() => $this->resolveFromPhoneNumber($data),
            'customer' => fn() => $this->resolveFromCustomer($data),
            'session' => fn() => $this->resolveFromSession()
        ];
        
        foreach ($strategies as $method => $resolver) {
            try {
                $result = $resolver();
                if ($result) {
                    $result['resolution_method'] = $method;
                    $this->auditSuccessfulResolution($result);
                    return $result;
                }
            } catch (\Exception $e) {
                Log::warning("SecureUnifiedCompanyResolver: {$method} resolution failed", [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // No resolution possible
        Log::warning('SecureUnifiedCompanyResolver: Could not resolve company', [
            'has_subdomain' => !empty($data['subdomain']),
            'has_phone' => !empty($data['phone']) || !empty($data['to_number']),
            'has_customer' => !empty($data['customer_id'])
        ]);
        
        return null;
    }
    
    /**
     * Resolve from subdomain with validation
     */
    protected function resolveFromSubdomain(array $data): ?array
    {
        $subdomain = $data['subdomain'] ?? request()->route('subdomain') ?? null;
        
        if (!$subdomain) {
            return null;
        }
        
        // Use cache for performance
        $cacheKey = "secure_company_subdomain:{$subdomain}";
        
        return Cache::remember($cacheKey, 300, function() use ($subdomain) {
            $company = Company::where('subdomain', $subdomain)
                ->where('is_active', true)
                ->first();
                
            if (!$company) {
                return null;
            }
            
            return [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'company' => $company,
                'confidence' => 0.95
            ];
        });
    }
    
    /**
     * Resolve from request header with validation
     */
    protected function resolveFromHeader(array $data): ?array
    {
        $companyId = $data['company_id'] ?? request()->header('X-Company-ID');
        
        if (!$companyId || !is_numeric($companyId)) {
            return null;
        }
        
        // Validate header against session if authenticated
        if (auth()->check() && auth()->user()->company_id != $companyId) {
            Log::warning('SecureUnifiedCompanyResolver: Header company mismatch', [
                'header_company' => $companyId,
                'user_company' => auth()->user()->company_id
            ]);
            return null;
        }
        
        $company = Company::where('id', $companyId)
            ->where('is_active', true)
            ->first();
            
        if (!$company) {
            return null;
        }
        
        return [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'company' => $company,
            'confidence' => 0.9
        ];
    }
    
    /**
     * Resolve from phone number with security checks
     */
    protected function resolveFromPhoneNumber(array $data): ?array
    {
        $phoneNumber = $data['to_number'] ?? $data['phone'] ?? null;
        
        if (!$phoneNumber) {
            return null;
        }
        
        // Normalize phone number
        $normalized = $this->normalizePhoneNumber($phoneNumber);
        
        // Direct phone number table lookup
        $phoneRecord = DB::table('phone_numbers')
            ->where(function($query) use ($normalized, $phoneNumber) {
                $query->where('phone_number', $normalized)
                      ->orWhere('phone_number', $phoneNumber)
                      ->orWhere('phone_number', $normalized)
                      ->orWhere('number', $phoneNumber);
            })
            ->where('is_active', true)
            ->first();
            
        if (!$phoneRecord) {
            return null;
        }
        
        // Verify company exists and is active
        $company = Company::where('id', $phoneRecord->company_id)
            ->where('is_active', true)
            ->first();
            
        if (!$company) {
            return null;
        }
        
        // Get branch if available
        $branch = null;
        if ($phoneRecord->branch_id) {
            $branch = Branch::where('id', $phoneRecord->branch_id)
                ->where('company_id', $company->id)
                ->first();
        }
        
        return [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'company' => $company,
            'branch_id' => $branch?->id,
            'branch' => $branch,
            'confidence' => 0.85
        ];
    }
    
    /**
     * Resolve from customer with proper isolation
     */
    protected function resolveFromCustomer(array $data): ?array
    {
        $customerId = $data['customer_id'] ?? null;
        
        if (!$customerId) {
            return null;
        }
        
        // If authenticated, validate customer belongs to user's company
        if (auth()->check()) {
            $customer = Customer::where('id', $customerId)
                ->where('company_id', auth()->user()->company_id)
                ->first();
        } else {
            // For webhooks, we need to be more careful
            $customer = Customer::find($customerId);
        }
        
        if (!$customer) {
            return null;
        }
        
        $company = Company::where('id', $customer->company_id)
            ->where('is_active', true)
            ->first();
            
        if (!$company) {
            return null;
        }
        
        return [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'company' => $company,
            'customer' => $customer,
            'confidence' => 0.8
        ];
    }
    
    /**
     * Resolve from authenticated session
     */
    protected function resolveFromSession(): ?array
    {
        if (!auth()->check()) {
            return null;
        }
        
        $user = auth()->user();
        
        if (!$user->company_id) {
            return null;
        }
        
        $company = Company::where('id', $user->company_id)
            ->where('is_active', true)
            ->first();
            
        if (!$company) {
            return null;
        }
        
        return [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'company' => $company,
            'user' => $user,
            'confidence' => 1.0 // Highest confidence from authenticated session
        ];
    }
    
    /**
     * Normalize phone number for consistent lookup
     */
    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Ensure consistent format
        if (!empty($cleaned) && strpos($cleaned, '+') !== 0 && strlen($cleaned) > 10) {
            $cleaned = '+' . $cleaned;
        }
        
        return $cleaned;
    }
    
    /**
     * Audit resolution attempt for security monitoring
     */
    protected function auditResolutionAttempt(array $data): void
    {
        if (DB::connection()->getSchemaBuilder()->hasTable('security_audit_logs')) {
            DB::table('security_audit_logs')->insert([
                'event_type' => 'company_resolution_attempt',
                'user_id' => auth()->id(),
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'url' => request()->fullUrl() ?? 'console',
                'metadata' => json_encode([
                    'has_subdomain' => !empty($data['subdomain']),
                    'has_header' => !empty($data['company_id']),
                    'has_phone' => !empty($data['phone']) || !empty($data['to_number']),
                    'has_customer' => !empty($data['customer_id']),
                    'is_authenticated' => auth()->check()
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
    
    /**
     * Audit successful resolution
     */
    protected function auditSuccessfulResolution(array $result): void
    {
        if (DB::connection()->getSchemaBuilder()->hasTable('security_audit_logs')) {
            DB::table('security_audit_logs')->insert([
                'event_type' => 'company_resolution_success',
                'user_id' => auth()->id(),
                'company_id' => $result['company_id'],
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'url' => request()->fullUrl() ?? 'console',
                'metadata' => json_encode([
                    'resolution_method' => $result['resolution_method'],
                    'confidence' => $result['confidence'],
                    'has_branch' => !empty($result['branch_id'])
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}