<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\Agent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PhoneNumberResolver
{
    /**
     * Resolve branch and agent from phone number or metadata
     */
    public function resolveFromWebhook(array $webhookData): array
    {
        // 1. Try to get from Retell metadata (if agent has branch_id stored)
        if (isset($webhookData['metadata']['askproai_branch_id'])) {
            $branchId = $webhookData['metadata']['askproai_branch_id'];
            $branch = Branch::find($branchId);
            
            if ($branch) {
                Log::info('Branch resolved from metadata', [
                    'branch_id' => $branchId,
                    'branch_name' => $branch->name
                ]);
                
                return [
                    'branch_id' => $branch->id,
                    'company_id' => $branch->company_id,
                    'agent_id' => $this->resolveAgentId($webhookData, $branch)
                ];
            }
        }
        
        // 2. Try to resolve from to_number (Retell sends it as 'to')
        $toNumber = $webhookData['to'] ?? $webhookData['to_number'] ?? $webhookData['destination_number'] ?? null;
        if ($toNumber) {
            $result = $this->resolveFromPhoneNumber($toNumber);
            if ($result) {
                return $result;
            }
        }
        
        // 3. Try to resolve from agent_id
        $retellAgentId = $webhookData['agent_id'] ?? null;
        if ($retellAgentId) {
            $result = $this->resolveFromAgentId($retellAgentId);
            if ($result) {
                return $result;
            }
        }
        
        // 4. Fallback to company from webhook
        return [
            'branch_id' => null,
            'company_id' => $webhookData['company_id'] ?? null,
            'agent_id' => null
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
            // 1. Check phone_numbers table
            $phoneRecord = PhoneNumber::where('number', $normalized)
                ->orWhere('number', $phoneNumber)
                ->where('active', true)
                ->with('branch')
                ->first();
                
            if ($phoneRecord && $phoneRecord->branch) {
                Log::info('Branch resolved from phone_numbers table', [
                    'number' => $phoneNumber,
                    'branch_id' => $phoneRecord->branch_id
                ]);
                
                return [
                    'branch_id' => $phoneRecord->branch_id,
                    'company_id' => $phoneRecord->branch->company_id,
                    'agent_id' => $phoneRecord->agent_id
                ];
            }
            
            // 2. Check branch main phone number
            $branch = Branch::where('phone_number', $normalized)
                ->orWhere('phone_number', $phoneNumber)
                ->first();
                
            if ($branch) {
                Log::info('Branch resolved from main phone number', [
                    'number' => $phoneNumber,
                    'branch_id' => $branch->id
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
        // 1. Check if we have a local Agent record
        $agent = Agent::where('agent_id', $retellAgentId)
            ->orWhere('retell_agent_id', $retellAgentId)
            ->first();
            
        if ($agent && $agent->branch_id) {
            return [
                'branch_id' => $agent->branch_id,
                'company_id' => $agent->company_id,
                'agent_id' => $agent->id
            ];
        }
        
        // 2. Check branches for this agent
        $branch = Branch::where('retell_agent_id', $retellAgentId)->first();
        
        if ($branch) {
            Log::info('Branch resolved from agent ID', [
                'agent_id' => $retellAgentId,
                'branch_id' => $branch->id
            ]);
            
            return [
                'branch_id' => $branch->id,
                'company_id' => $branch->company_id,
                'agent_id' => $agent?->id
            ];
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
        
        // Find local agent record
        $agent = Agent::where('agent_id', $retellAgentId)
            ->orWhere('retell_agent_id', $retellAgentId)
            ->first();
            
        return $agent?->id;
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
}