<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\PhoneNumber;
use App\Models\Agent;
use App\Models\Customer;
use App\Models\Call;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * SECURE VERSION: Phone Number Resolver with proper tenant isolation
 * 
 * This service resolves phone numbers to companies/branches while maintaining
 * security boundaries. It's designed for webhook processing where we don't
 * know the tenant context upfront.
 */
class SecurePhoneNumberResolver
{
    /**
     * Audit all cross-tenant access attempts
     */
    private function auditAccess(string $action, array $context): void
    {
        if (Schema::hasTable('security_audit_logs')) {
            DB::table('security_audit_logs')->insert([
                'event_type' => 'phone_resolution',
                'user_id' => auth()->id(),
                'company_id' => $context['company_id'] ?? null,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'url' => request()->fullUrl() ?? 'console',
                'metadata' => json_encode(array_merge($context, ['action' => $action])),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    
    /**
     * Resolve phone number to company context securely
     */
    public function resolve(string $phoneNumber): array
    {
        $phoneNumber = $this->validatePhoneNumber($phoneNumber);
        
        // Audit the resolution attempt
        $this->auditAccess('resolve_attempt', ['phone_number' => substr($phoneNumber, 0, -4) . '****']);
        
        // Check table structure
        if (!Schema::hasColumn('phone_numbers', 'phone_number') && !Schema::hasColumn('phone_numbers', 'phone_number')) {
            Log::error('SecurePhoneNumberResolver: phone_numbers table missing required columns');
            return ['found' => false, 'normalized_number' => $phoneNumber];
        }
        
        $numberColumn = Schema::hasColumn('phone_numbers', 'phone_number') ? 'number' : 'phone_number';
        
        // Use raw query with proper constraints for security
        $phoneRecord = DB::table('phone_numbers')
            ->where($numberColumn, $phoneNumber)
            ->where('is_active', true)
            ->first();
            
        if (!$phoneRecord) {
            // Try without + prefix
            if (strpos($phoneNumber, '+') === 0) {
                $phoneWithoutPlus = substr($phoneNumber, 1);
                $phoneRecord = DB::table('phone_numbers')
                    ->where($numberColumn, $phoneWithoutPlus)
                    ->where('is_active', true)
                    ->first();
            }
        }
        
        if ($phoneRecord) {
            // Verify branch exists and is active
            $branch = DB::table('branches')
                ->where('id', $phoneRecord->branch_id)
                ->where('company_id', $phoneRecord->company_id)
                ->first();
                
            if ($branch) {
                $company = DB::table('companies')
                    ->where('id', $phoneRecord->company_id)
                    ->first();
                    
                $result = [
                    'found' => true,
                    'normalized_number' => $phoneNumber,
                    'phone_record_id' => $phoneRecord->id,
                    'company_id' => $phoneRecord->company_id,
                    'company_name' => $company->name ?? 'Unknown',
                    'branch_id' => $phoneRecord->branch_id,
                    'branch_name' => $branch->name ?? 'Unknown',
                    'agent_id' => $phoneRecord->agent_id ?? null,
                ];
                
                // Audit successful resolution
                $this->auditAccess('resolve_success', [
                    'company_id' => $phoneRecord->company_id,
                    'branch_id' => $phoneRecord->branch_id,
                ]);
                
                return $result;
            }
        }
        
        // Not found - this is expected for unknown numbers
        return [
            'found' => false,
            'normalized_number' => $phoneNumber
        ];
    }
    
    /**
     * Resolve webhook data to company context with multiple strategies
     * This is the main entry point for webhook processing
     */
    public function resolveWebhookData(array $webhookData): array
    {
        // Audit webhook resolution attempt
        $this->auditAccess('webhook_resolve_attempt', [
            'has_to_number' => isset($webhookData['to']) || isset($webhookData['to_number']),
            'has_agent_id' => isset($webhookData['retell_agent_id']) || isset($webhookData['agent_id']),
        ]);
        
        // Strategy 1: Direct phone number resolution (most reliable)
        $toNumber = $webhookData['to'] ?? $webhookData['to_number'] ?? null;
        if ($toNumber) {
            $result = $this->resolveFromPhoneNumberSecure($toNumber);
            if ($result) {
                $result['resolution_method'] = 'phone_number';
                $result['confidence'] = 0.9;
                return $result;
            }
        }
        
        // Strategy 2: Agent ID resolution
        $retellAgentId = $webhookData['retell_agent_id'] ?? $webhookData['agent_id'] ?? null;
        if ($retellAgentId) {
            $result = $this->resolveFromAgentIdSecure($retellAgentId);
            if ($result) {
                $result['resolution_method'] = 'agent_id';
                $result['confidence'] = 0.85;
                return $result;
            }
        }
        
        // Strategy 3: Recent call history (for callbacks)
        $fromNumber = $webhookData['from'] ?? $webhookData['from_number'] ?? null;
        if ($fromNumber) {
            $result = $this->resolveFromCallerHistorySecure($fromNumber);
            if ($result) {
                $result['resolution_method'] = 'caller_history';
                $result['confidence'] = 0.7;
                return $result;
            }
        }
        
        // Log unresolved calls for investigation
        Log::warning('SecurePhoneNumberResolver: Could not resolve company/branch', [
            'to_number' => substr($toNumber ?? 'unknown', 0, -4) . '****',
            'from_number' => substr($fromNumber ?? 'unknown', 0, -4) . '****',
            'agent_id' => $retellAgentId ?? 'unknown',
        ]);
        
        // Return null result - let the caller decide fallback
        return [
            'branch_id' => null,
            'company_id' => null,
            'agent_id' => null,
            'resolution_method' => 'unresolved',
            'confidence' => 0
        ];
    }
    
    /**
     * Resolve from phone number with security constraints
     */
    protected function resolveFromPhoneNumberSecure(string $phoneNumber): ?array
    {
        $normalized = $this->normalizePhoneNumber($phoneNumber);
        
        // Use cache for performance, but include security context
        $cacheKey = "secure_phone_resolver:{$normalized}";
        
        return Cache::remember($cacheKey, 300, function() use ($normalized, $phoneNumber) {
            // Direct DB query for security
            $phoneRecord = DB::table('phone_numbers')
                ->where(function($query) use ($normalized, $phoneNumber) {
                    $numberColumn = Schema::hasColumn('phone_numbers', 'phone_number') ? 'number' : 'phone_number';
                    $query->where($numberColumn, $normalized)
                          ->orWhere($numberColumn, $phoneNumber);
                })
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();
                
            if (!$phoneRecord) {
                return null;
            }
            
            // Verify branch is active
            $branch = DB::table('branches')
                ->where('id', $phoneRecord->branch_id)
                ->where('company_id', $phoneRecord->company_id)
                ->first();
                
            if (!$branch) {
                Log::warning('SecurePhoneNumberResolver: Phone number has invalid branch', [
                    'phone_id' => $phoneRecord->id,
                    'branch_id' => $phoneRecord->branch_id,
                ]);
                return null;
            }
            
            return [
                'branch_id' => $phoneRecord->branch_id,
                'company_id' => $phoneRecord->company_id,
                'agent_id' => $phoneRecord->agent_id ?? null,
            ];
        });
    }
    
    /**
     * Resolve from agent ID with security constraints
     */
    protected function resolveFromAgentIdSecure(string $agentId): ?array
    {
        // Direct DB query for security
        $agent = DB::table('agents')
            ->where('retell_agent_id', $agentId)
            ->where('is_active', true)
            ->first();
            
        if (!$agent) {
            return null;
        }
        
        // Verify branch is active
        if ($agent->branch_id) {
            $branch = DB::table('branches')
                ->where('id', $agent->branch_id)
                ->where('company_id', $agent->company_id)
                ->first();
                
            if (!$branch) {
                return null;
            }
        }
        
        return [
            'branch_id' => $agent->branch_id,
            'company_id' => $agent->company_id,
            'agent_id' => $agent->id,
        ];
    }
    
    /**
     * Resolve from caller history with security constraints
     */
    protected function resolveFromCallerHistorySecure(string $callerNumber): ?array
    {
        $normalized = $this->normalizePhoneNumber($callerNumber);
        
        // Look for recent calls from this number (last 7 days)
        $recentCall = DB::table('calls')
            ->where(function($query) use ($normalized, $callerNumber) {
                $query->where('from_number', $normalized)
                      ->orWhere('from_number', $callerNumber);
            })
            ->where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('company_id')
            ->whereNotNull('branch_id')
            ->orderBy('created_at', 'desc')
            ->first();
            
        if (!$recentCall) {
            return null;
        }
        
        // Verify branch still exists
        $branch = DB::table('branches')
            ->where('id', $recentCall->branch_id)
            ->where('company_id', $recentCall->company_id)
            ->first();
            
        if (!$branch) {
            return null;
        }
        
        return [
            'branch_id' => $recentCall->branch_id,
            'company_id' => $recentCall->company_id,
            'agent_id' => $recentCall->agent_id ?? null,
        ];
    }
    
    /**
     * Validate and normalize phone number
     */
    protected function validatePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        if (empty($cleaned)) {
            return '';
        }
        
        return $cleaned;
    }
    
    /**
     * Normalize phone number for consistent storage
     */
    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        $cleaned = $this->validatePhoneNumber($phoneNumber);
        
        // Ensure consistent format
        if (!empty($cleaned) && strpos($cleaned, '+') !== 0 && strlen($cleaned) > 10) {
            $cleaned = '+' . $cleaned;
        }
        
        return $cleaned;
    }
    
    /**
     * Get phone numbers for a specific company (with proper tenant context)
     */
    public function getCompanyPhoneNumbers(int $companyId): array
    {
        // This method requires tenant context - verify access
        if (auth()->check() && auth()->user()->company_id !== $companyId) {
            // Check if user has cross-tenant permissions
            if (!auth()->user()->hasRole(['super_admin'])) {
                Log::warning('SecurePhoneNumberResolver: Unauthorized company phone access attempt', [
                    'user_id' => auth()->id(),
                    'user_company' => auth()->user()->company_id,
                    'requested_company' => $companyId,
                ]);
                return [];
            }
        }
        
        $phoneNumbers = PhoneNumber::where('company_id', $companyId)
            ->with(['branch:id,name', 'agent:id,name'])
            ->get();
            
        return $phoneNumbers->map(function($phone) {
            $numberColumn = Schema::hasColumn('phone_numbers', 'phone_number') ? 'number' : 'phone_number';
            return [
                'id' => $phone->id,
                'number' => $phone->$numberColumn,
                'branch' => $phone->branch ? $phone->branch->name : null,
                'agent' => $phone->agent ? $phone->agent->name : null,
                'is_active' => $phone->is_active,
            ];
        })->toArray();
    }
}