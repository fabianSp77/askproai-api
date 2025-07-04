<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\PhoneNumber;
use App\Models\Agent;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PhoneNumberResolver
{
    /**
     * Simple resolve method for MCPContextResolver compatibility
     */
    public function resolve(string $phoneNumber): array
    {
        // Validate phone number format
        $phoneNumber = $this->validatePhoneNumber($phoneNumber);
        // Check if phone_numbers table has 'number' column
        if (!Schema::hasColumn('phone_numbers', 'number')) {
            Log::error('PhoneNumberResolver: phone_numbers table missing number column');
            return ['found' => false, 'normalized_number' => $phoneNumber];
        }
        
        // Try to find phone number in database
        $phoneRecord = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('number', $phoneNumber)
            ->where('is_active', true)
            ->first();
            
        if (!$phoneRecord) {
            // Try without + prefix
            if (strpos($phoneNumber, '+') === 0) {
                $phoneWithoutPlus = substr($phoneNumber, 1);
                $phoneRecord = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('number', $phoneWithoutPlus)
                    ->where('is_active', true)
                    ->first();
            }
        }
        
        if ($phoneRecord) {
            // Get branch and company names without loading relationships
            $branchName = null;
            $companyName = null;
            
            if ($phoneRecord->branch_id) {
                $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->find($phoneRecord->branch_id);
                $branchName = $branch ? $branch->name : null;
            }
            
            if ($phoneRecord->company_id) {
                $company = Company::find($phoneRecord->company_id);
                $companyName = $company ? $company->name : null;
            }
            
            return [
                'found' => true,
                'phone_id' => $phoneRecord->id,
                'branch_id' => $phoneRecord->branch_id,
                'branch_name' => $branchName,
                'company_id' => $phoneRecord->company_id,
                'company_name' => $companyName,
                'agent_id' => $phoneRecord->retell_agent_id,
                'normalized_number' => $phoneRecord->number
            ];
        }
        
        // Fallback: Check branches table
        $normalized = $this->normalizePhoneNumber($phoneNumber);
        $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where(function($query) use ($phoneNumber, $normalized) {
                $query->where('phone', $phoneNumber)
                      ->orWhere('phone', $normalized);
            })
            ->where('is_active', true)
            ->first();
            
        if ($branch) {
            return [
                'found' => true,
                'branch_id' => $branch->id,
                'company_id' => $branch->company_id,
                'agent_id' => null,
                'normalized_number' => $phoneNumber
            ];
        }
        
        return ['found' => false, 'normalized_number' => $phoneNumber];
    }
    
    /**
     * Resolve branch and agent from phone number or metadata
     * Enhanced for multi-location support
     */
    public function resolveFromWebhook(array $webhookData): array
    {
        $resolution = [
            'branch_id' => null,
            'company_id' => null,
            'agent_id' => null,
            'resolution_method' => null,
            'confidence' => 0
        ];
        
        // 0. Check for test mode
        if ($this->isTestMode($webhookData)) {
            return $this->resolveTestModeContext($webhookData);
        }
        
        // 1. Try to get from Retell metadata (if agent has branch_id stored)
        if (isset($webhookData['metadata']['askproai_branch_id'])) {
            $branchId = $webhookData['metadata']['askproai_branch_id'];
            // Security: Don't bypass tenant scope - verify branch belongs to a valid company
            $branch = Branch::find($branchId);
            
            // If not found in current scope, check without scope but verify company
            if (!$branch) {
                $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('id', $branchId)
                    ->where('is_active', true)
                    ->first();
                    
                // Verify branch has valid company
                if ($branch && (!$branch->company_id || !Company::find($branch->company_id))) {
                    Log::warning('Branch found but company invalid', [
                        'branch_id' => $branchId,
                        'company_id' => $branch->company_id
                    ]);
                    $branch = null;
                }
            }
            
            if ($branch && $branch->is_active) {
                Log::info('Branch resolved from metadata', [
                    'branch_id' => $branchId,
                    'branch_name' => $branch->name
                ]);
                
                return [
                    'branch_id' => $branch->id,
                    'company_id' => $branch->company_id,
                    'agent_id' => $this->resolveAgentId($webhookData, $branch),
                    'resolution_method' => 'metadata',
                    'confidence' => 1.0
                ];
            }
        }
        
        // 2. Try to resolve from to_number (Retell sends it as 'to')
        $toNumber = $webhookData['to'] ?? $webhookData['to_number'] ?? $webhookData['destination_number'] ?? null;
        if ($toNumber) {
            $result = $this->resolveFromPhoneNumber($toNumber);
            if ($result) {
                $result['resolution_method'] = 'phone_number';
                $result['confidence'] = 0.9;
                return $result;
            }
        }
        
        // 3. Try to resolve from agent_id
        $retellAgentId = $webhookData['agent_id'] ?? null;
        if ($retellAgentId) {
            $result = $this->resolveFromAgentId($retellAgentId);
            if ($result) {
                $result['resolution_method'] = 'agent_id';
                $result['confidence'] = 0.8;
                return $result;
            }
        }
        
        // 4. Try to resolve from caller context (repeat customer)
        $fromNumber = $webhookData['from'] ?? $webhookData['from_number'] ?? null;
        if ($fromNumber) {
            $result = $this->resolveFromCallerHistory($fromNumber);
            if ($result) {
                $result['resolution_method'] = 'caller_history';
                $result['confidence'] = 0.7;
                return $result;
            }
        }
        
        // 5. Enhanced fallback mechanism with multiple strategies
        $fallbackResult = $this->enhancedFallback($webhookData);
        if ($fallbackResult) {
            $fallbackResult['resolution_method'] = 'enhanced_fallback';
            $fallbackResult['confidence'] = 0.5;
            return $fallbackResult;
        }
        
        // 6. Final fallback to company from webhook or default
        $companyId = $webhookData['company_id'] ?? Company::first()->id ?? null;
        
        // Log warning for unresolved calls
        Log::warning('Could not resolve company/branch for incoming call', [
            'to_number' => $toNumber ?? 'unknown',
            'from_number' => $fromNumber ?? 'unknown',
            'agent_id' => $retellAgentId ?? 'unknown',
            'webhook_data_keys' => array_keys($webhookData)
        ]);
        
        return [
            'branch_id' => null,
            'company_id' => $companyId,
            'agent_id' => null,
            'resolution_method' => 'fallback',
            'confidence' => 0.3
        ];
    }
    
    /**
     * Resolve from phone number
     */
    protected function resolveFromPhoneNumber(string $phoneNumber): ?array
    {
        // Normalize phone number
        $normalized = $this->normalizePhoneNumber($phoneNumber);
        
        // Cache key for performance
        $cacheKey = "phone_resolver:{$normalized}";
        
        return Cache::remember($cacheKey, 300, function() use ($normalized, $phoneNumber) {
            // 1. Check phone_numbers table (with proper tenant context)
            // We need to search across all companies since incoming calls don't have company context
            $phoneRecord = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where(function($query) use ($normalized, $phoneNumber) {
                    $query->where(function($q) use ($normalized, $phoneNumber) {
                    $numberColumn = Schema::hasColumn('phone_numbers', 'number') ? 'number' : 'phone_number';
                    $q->where($numberColumn, $normalized)
                      ->orWhere($numberColumn, $phoneNumber);
                });
                })
                ->where('is_active', true)
                ->first();
                
            if ($phoneRecord && $phoneRecord->branch_id) {
                // Manually check if branch is active
                $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('id', $phoneRecord->branch_id)
                    ->where('is_active', true)
                    ->first();
                    
                if ($branch) {
                    Log::info('Branch resolved from phone_numbers table', [
                        'number' => $phoneNumber,
                        'branch_id' => $phoneRecord->branch_id,
                        'branch_active' => true
                    ]);
                    
                    return [
                        'branch_id' => $phoneRecord->branch_id,
                        'company_id' => $branch->company_id,
                        'agent_id' => $phoneRecord->retell_agent_id ?? $phoneRecord->agent_id
                    ];
                }
            }
            
            // 2. Check branch main phone number (only active branches)
            $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where(function($query) use ($normalized, $phoneNumber) {
                    $query->where('phone_number', $normalized)
                          ->orWhere('phone_number', $phoneNumber);
                })
                ->where('is_active', true)
                ->first();
                
            if ($branch) {
                Log::info('Branch resolved from main phone number', [
                    'number' => $phoneNumber,
                    'branch_id' => $branch->id,
                    'active' => true
                ]);
                
                return [
                    'branch_id' => $branch->id,
                    'company_id' => $branch->company_id,
                    'agent_id' => null
                ];
            }
            
            return null;
        });
    }
    
    /**
     * Resolve from Retell agent ID
     */
    protected function resolveFromAgentId(string $retellAgentId): ?array
    {
        // 1. Try to check if we have a local Agent record (if table has retell_agent_id column)
        try {
            if (Schema::hasTable('agents') && Schema::hasColumn('agents', 'retell_agent_id')) {
                $agent = Agent::where('retell_agent_id', $retellAgentId)
                    ->first();
                    
                if ($agent && $agent->branch_id) {
                    return [
                        'branch_id' => $agent->branch_id,
                        'company_id' => $agent->company_id,
                        'agent_id' => $agent->id
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not query agents table', ['error' => $e->getMessage()]);
        }
        
        // 2. Check branches for this agent (if column exists) - only active branches
        try {
            if (Schema::hasColumn('branches', 'retell_agent_id')) {
                $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('retell_agent_id', $retellAgentId)
                    ->where('is_active', true)
                    ->first();
                
                if ($branch) {
                    Log::info('Branch resolved from agent ID', [
                        'agent_id' => $retellAgentId,
                        'branch_id' => $branch->id,
                        'active' => true
                    ]);
                    
                    return [
                        'branch_id' => $branch->id,
                        'company_id' => $branch->company_id,
                        'agent_id' => null // No agent record available
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not query branches for retell_agent_id', ['error' => $e->getMessage()]);
        }
        
        // 3. Fallback: Use first branch of first company
        $company = Company::first();
        if ($company) {
            $branch = $company->branches()->first();
            if ($branch) {
                Log::info('Using fallback branch resolution', [
                    'agent_id' => $retellAgentId,
                    'branch_id' => $branch->id,
                    'company_id' => $company->id
                ]);
                
                return [
                    'branch_id' => $branch->id,
                    'company_id' => $company->id,
                    'agent_id' => null
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Resolve agent ID from webhook and branch
     */
    protected function resolveAgentId(array $webhookData, ?Branch $branch): ?int
    {
        $retellAgentId = $webhookData['agent_id'] ?? null;
        
        if (!$retellAgentId) {
            return null;
        }
        
        // Find local agent record (if column exists)
        try {
            if (Schema::hasTable('agents') && Schema::hasColumn('agents', 'retell_agent_id')) {
                $agent = Agent::where('retell_agent_id', $retellAgentId)
                    ->first();
                    
                return $agent?->id;
            }
        } catch (\Exception $e) {
            Log::warning('Could not find agent by retell_agent_id', ['error' => $e->getMessage()]);
        }
        
        return null;
    }
    
    /**
     * Resolve from caller history - find branch based on previous interactions
     */
    protected function resolveFromCallerHistory(string $callerNumber): ?array
    {
        $normalized = $this->normalizePhoneNumber($callerNumber);
        
        // Look for existing customer
        $customer = Customer::where('phone', $normalized)
            ->orWhere('phone', $callerNumber)
            ->orderBy('updated_at', 'desc')
            ->first();
            
        if ($customer) {
            // Check for preferred branch
            if ($customer->preferred_branch_id) {
                $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('id', $customer->preferred_branch_id)
                    ->where('is_active', true)
                    ->first();
                    
                if ($branch) {
                    Log::info('Branch resolved from customer preference', [
                        'customer_id' => $customer->id,
                        'branch_id' => $branch->id
                    ]);
                    
                    return [
                        'branch_id' => $branch->id,
                        'company_id' => $branch->company_id,
                        'agent_id' => null
                    ];
                }
            }
            
            // Check last appointment branch
            $lastAppointment = Appointment::where('customer_id', $customer->id)
                ->whereNotNull('branch_id')
                ->orderBy('created_at', 'desc')
                ->first();
                
            if ($lastAppointment && $lastAppointment->branch && $lastAppointment->branch->is_active) {
                Log::info('Branch resolved from last appointment', [
                    'customer_id' => $customer->id,
                    'branch_id' => $lastAppointment->branch_id
                ]);
                
                return [
                    'branch_id' => $lastAppointment->branch_id,
                    'company_id' => $lastAppointment->company_id,
                    'agent_id' => null
                ];
            }
            
            // Use customer's registered branch
            if ($customer->branch_id) {
                $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('id', $customer->branch_id)
                    ->where('is_active', true)
                    ->first();
                    
                if ($branch) {
                    return [
                        'branch_id' => $branch->id,
                        'company_id' => $branch->company_id,
                        'agent_id' => null
                    ];
                }
            }
        }
        
        // Check call history
        $lastCall = Call::where('from_number', $normalized)
            ->orWhere('from_number', $callerNumber)
            ->whereNotNull('branch_id')
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($lastCall && $lastCall->branch && $lastCall->branch->is_active) {
            Log::info('Branch resolved from call history', [
                'phone' => $callerNumber,
                'branch_id' => $lastCall->branch_id
            ]);
            
            return [
                'branch_id' => $lastCall->branch_id,
                'company_id' => $lastCall->company_id,
                'agent_id' => null
            ];
        }
        
        return null;
    }
    
    /**
     * Normalize phone number for comparison
     */
    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Add country code if missing (assuming Germany)
        if (strlen($cleaned) === 10 && substr($cleaned, 0, 1) === '0') {
            // German national format (030...) -> international (+4930...)
            $cleaned = '49' . substr($cleaned, 1);
        }
        
        return '+' . $cleaned;
    }
    
    /**
     * Public method to normalize phone numbers
     */
    public function normalize(string $phoneNumber): string
    {
        return $this->normalizePhoneNumber($phoneNumber);
    }
    
    /**
     * Resolve context from phone number (alias for resolveFromPhoneNumber)
     * Used by MCP servers
     */
    public function resolveFromPhone(string $phoneNumber): array
    {
        $result = $this->resolveFromPhoneNumber($phoneNumber);
        
        if ($result) {
            return array_merge($result, ['found' => true]);
        }
        
        // Fallback
        return [
            'found' => false,
            'company_id' => Company::first()->id ?? null,
            'branch_id' => null,
            'company' => Company::first(),
            'branch' => null,
        ];
    }
    
    /**
     * Validate phone number format
     * @throws \InvalidArgumentException
     */
    protected function validatePhoneNumber(string $phoneNumber): string
    {
        // Remove all whitespace and special characters except + and digits
        $cleaned = preg_replace('/[^0-9+]/', '', trim($phoneNumber));
        
        // Check if empty
        if (empty($cleaned)) {
            throw new \InvalidArgumentException('Phone number cannot be empty');
        }
        
        // Validate E.164 format (+ followed by 1-15 digits)
        if (!preg_match('/^\+?[1-9]\d{1,14}$/', $cleaned)) {
            throw new \InvalidArgumentException('Invalid phone number format. Must be in E.164 format.');
        }
        
        return $cleaned;
    }
    
    /**
     * Check if this is a test mode call
     */
    protected function isTestMode(array $webhookData): bool
    {
        // Check multiple indicators for test mode
        return 
            // Environment variable
            env('RETELL_TEST_MODE', false) ||
            // Metadata flag
            (isset($webhookData['metadata']['test_mode']) && $webhookData['metadata']['test_mode']) ||
            // Test phone numbers
            in_array($webhookData['to'] ?? '', ['+15551234567', '+4915551234567', '+491234567890']) ||
            // Test agent IDs
            in_array($webhookData['agent_id'] ?? '', ['test_agent', 'demo_agent']);
    }
    
    /**
     * Resolve context for test mode
     */
    protected function resolveTestModeContext(array $webhookData): array
    {
        // Get or create test company
        $testCompany = Company::where('slug', 'test-company')->first();
        if (!$testCompany) {
            $testCompany = Company::first();
        }
        
        // Get or create test branch
        $testBranch = null;
        if ($testCompany) {
            // Use withoutGlobalScope to avoid tenant issues
            $testBranch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('company_id', $testCompany->id)
                ->where(function($query) {
                    $query->where('slug', 'test-branch')
                          ->orWhere('name', 'Test Branch');
                })
                ->first();
                
            if (!$testBranch) {
                $testBranch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('company_id', $testCompany->id)
                    ->first();
            }
        }
        
        Log::info('Test mode activated for webhook processing', [
            'company_id' => $testCompany?->id,
            'branch_id' => $testBranch?->id,
            'webhook_data' => array_keys($webhookData)
        ]);
        
        return [
            'branch_id' => $testBranch?->id,
            'company_id' => $testCompany?->id,
            'agent_id' => null,
            'resolution_method' => 'test_mode',
            'confidence' => 1.0
        ];
    }
    
    /**
     * Enhanced fallback mechanism with multiple strategies
     */
    protected function enhancedFallback(array $webhookData): ?array
    {
        // Strategy 1: Check for phone numbers in metadata
        if (isset($webhookData['metadata']['phone_number'])) {
            $result = $this->resolveFromPhoneNumber($webhookData['metadata']['phone_number']);
            if ($result) {
                Log::info('Resolved from metadata phone number', $result);
                return $result;
            }
        }
        
        // Strategy 2: Check for company name in metadata or transcript
        if (isset($webhookData['metadata']['company_name'])) {
            $company = Company::where('name', 'like', '%' . $webhookData['metadata']['company_name'] . '%')
                ->orWhere('slug', 'like', '%' . str_slug($webhookData['metadata']['company_name']) . '%')
                ->first();
                
            if ($company) {
                $branch = $company->branches()->where('is_active', true)->first();
                if ($branch) {
                    Log::info('Resolved from company name in metadata', [
                        'company_name' => $webhookData['metadata']['company_name'],
                        'company_id' => $company->id,
                        'branch_id' => $branch->id
                    ]);
                    
                    return [
                        'branch_id' => $branch->id,
                        'company_id' => $company->id,
                        'agent_id' => null
                    ];
                }
            }
        }
        
        // Strategy 3: Use the most recently active company/branch
        $recentCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->whereNotNull('branch_id')
            ->where('created_at', '>', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($recentCall && $recentCall->branch && $recentCall->branch->is_active) {
            Log::info('Using most recently active branch as fallback', [
                'branch_id' => $recentCall->branch_id,
                'last_call_at' => $recentCall->created_at
            ]);
            
            return [
                'branch_id' => $recentCall->branch_id,
                'company_id' => $recentCall->company_id,
                'agent_id' => null
            ];
        }
        
        // Strategy 4: Check for partial phone number matches
        $toNumber = $webhookData['to'] ?? $webhookData['to_number'] ?? null;
        if ($toNumber && strlen($toNumber) >= 6) {
            // Try to find a phone number that ends with the same digits
            $lastDigits = substr(preg_replace('/[^0-9]/', '', $toNumber), -6);
            
            $phoneRecord = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('number', 'like', '%' . $lastDigits)
                ->where('is_active', true)
                ->first();
                
            if ($phoneRecord && $phoneRecord->branch_id) {
                $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('id', $phoneRecord->branch_id)
                    ->where('is_active', true)
                    ->first();
                    
                if ($branch) {
                    Log::info('Resolved from partial phone number match', [
                        'partial_match' => $lastDigits,
                        'full_number' => $phoneRecord->number,
                        'branch_id' => $branch->id
                    ]);
                    
                    return [
                        'branch_id' => $branch->id,
                        'company_id' => $branch->company_id,
                        'agent_id' => $phoneRecord->retell_agent_id
                    ];
                }
            }
        }
        
        return null;
    }
    
    /**
     * Helper to create slug from string (if not available)
     */
    protected function str_slug($string): string
    {
        if (function_exists('str_slug')) {
            return str_slug($string);
        }
        
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    }
}