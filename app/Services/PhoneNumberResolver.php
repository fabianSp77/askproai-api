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
        // Check if phone_numbers table has 'number' column
        if (!Schema::hasColumn('phone_numbers', 'number')) {
            Log::error('PhoneNumberResolver: phone_numbers table missing number column');
            return ['found' => false, 'normalized_number' => $phoneNumber];
        }
        
        // Try to find phone number in database
        $phoneRecord = PhoneNumber::where('number', $phoneNumber)
            ->where('active', true)
            ->first();
            
        if (!$phoneRecord) {
            // Try without + prefix
            if (strpos($phoneNumber, '+') === 0) {
                $phoneWithoutPlus = substr($phoneNumber, 1);
                $phoneRecord = PhoneNumber::where('number', $phoneWithoutPlus)
                    ->where('active', true)
                    ->first();
            }
        }
        
        if ($phoneRecord) {
            return [
                'found' => true,
                'branch_id' => $phoneRecord->branch_id,
                'company_id' => $phoneRecord->company_id,
                'agent_id' => $phoneRecord->agent_id,
                'normalized_number' => $phoneRecord->number
            ];
        }
        
        // Fallback: Check branches table
        $branch = Branch::where('phone_number', $phoneNumber)
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
        
        // 1. Try to get from Retell metadata (if agent has branch_id stored)
        if (isset($webhookData['metadata']['askproai_branch_id'])) {
            $branchId = $webhookData['metadata']['askproai_branch_id'];
            $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($branchId);
            
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
        
        // 5. Fallback to company from webhook or default
        $companyId = $webhookData['company_id'] ?? Company::first()->id ?? null;
        
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
            // 1. Check phone_numbers table (only with active branches)
            $phoneRecord = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where(function($query) use ($normalized, $phoneNumber) {
                    $query->where('number', $normalized)
                          ->orWhere('number', $phoneNumber);
                })
                ->where('active', true)
                ->whereHas('branch', function($query) {
                    $query->withoutGlobalScope(\App\Scopes\TenantScope::class)
                          ->where('is_active', true);
                })
                ->with(['branch' => function($query) {
                    $query->withoutGlobalScope(\App\Scopes\TenantScope::class);
                }])
                ->first();
                
            if ($phoneRecord && $phoneRecord->branch) {
                Log::info('Branch resolved from phone_numbers table', [
                    'number' => $phoneNumber,
                    'branch_id' => $phoneRecord->branch_id,
                    'branch_active' => true
                ]);
                
                return [
                    'branch_id' => $phoneRecord->branch_id,
                    'company_id' => $phoneRecord->branch->company_id,
                    'agent_id' => $phoneRecord->agent_id
                ];
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
}