<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Company;

class PhoneAgentStatusWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.phone-agent-status';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;
    
    protected static ?string $heading = 'Phone Number → Agent Connections';
    
    public function getPhoneAgentData(): array
    {
        return Cache::remember('phone_agent_status_' . auth()->user()->company_id, 300, function () {
            // Get phone numbers for the current company
            // Note: We need to explicitly set company_id to work with TenantScope
            $companyId = auth()->user()->company_id;
            
            $phoneNumbers = PhoneNumber::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->with(['branch', 'retellAgent'])
                ->get();
            
            $data = [];
            $company = Company::find(auth()->user()->company_id);
            
            foreach ($phoneNumbers as $phone) {
                $syncStatus = 'unknown';
                $lastSync = null;
                $agentName = 'Not configured';
                $isOnline = false;
                
                if ($phone->retell_agent_id && $phone->retellAgent) {
                    $agentName = $phone->retellAgent->name ?? $phone->retell_agent_id;
                    $lastSync = $phone->retellAgent->last_synced_at;
                    
                    // Check if sync is recent (within last hour)
                    if ($lastSync && $lastSync->diffInHours(now()) < 1) {
                        $syncStatus = 'synced';
                    } elseif ($lastSync && $lastSync->diffInHours(now()) < 24) {
                        $syncStatus = 'stale';
                    } else {
                        $syncStatus = 'outdated';
                    }
                    
                    // Check if agent is actually online in Retell
                    $isOnline = $this->checkAgentOnlineStatus($phone->retell_agent_id, $company);
                }
                
                $data[] = [
                    'id' => $phone->id,
                    'number' => $phone->number,
                    'formatted_number' => $this->formatPhoneNumber($phone->number),
                    'branch' => $phone->branch->name ?? 'No branch',
                    'agent_id' => $phone->retell_agent_id,
                    'agent_name' => $agentName,
                    'sync_status' => $syncStatus,
                    'last_sync' => $lastSync,
                    'is_online' => $isOnline,
                    'type' => $phone->type,
                ];
            }
            
            return $data;
        });
    }
    
    protected function checkAgentOnlineStatus(string $agentId, $company): bool
    {
        if (!$company || !$company->retell_api_key) {
            return false;
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $company->retell_api_key,
            ])->get("https://api.retellai.com/get-agent/{$agentId}");
            
            if ($response->successful()) {
                $agent = $response->json();
                // Check if agent exists and is not deleted
                return isset($agent['agent_id']) && !($agent['deleted'] ?? false);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to check agent online status', [
                'agent_id' => $agentId,
                'error' => $e->getMessage()
            ]);
        }
        
        return false;
    }
    
    protected function formatPhoneNumber(string $number): string
    {
        // Format for German numbers
        if (str_starts_with($number, '+49')) {
            $cleaned = substr($number, 3);
            return '+49 ' . substr($cleaned, 0, 3) . ' ' . substr($cleaned, 3);
        }
        
        return $number;
    }
    
    public function syncPhoneAgent(string $phoneId): void
    {
        $phone = PhoneNumber::find($phoneId);
        if (!$phone || !$phone->retell_agent_id) {
            return;
        }
        
        $company = Company::find($phone->company_id);
        if (!$company || !$company->retell_api_key) {
            return;
        }
        
        try {
            // Fetch latest agent config from Retell
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $company->retell_api_key,
            ])->get("https://api.retellai.com/get-agent/{$phone->retell_agent_id}");
            
            if ($response->successful()) {
                $agentData = $response->json();
                
                // Update or create agent record
                RetellAgent::updateOrCreate(
                    [
                        'agent_id' => $phone->retell_agent_id,
                        'company_id' => $phone->company_id,
                    ],
                    [
                        'name' => $agentData['agent_name'] ?? null,
                        'configuration' => $agentData,
                        'last_synced_at' => now(),
                        'sync_status' => 'success',
                    ]
                );
                
                // Clear cache
                Cache::forget('phone_agent_status_' . $phone->company_id);
                
                $this->dispatch('phoneAgentSynced', ['phoneId' => $phoneId]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to sync phone agent', [
                'phone_id' => $phoneId,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('syncFailed', ['message' => 'Sync failed: ' . $e->getMessage()]);
        }
    }
    
    public function testCall(string $phoneId): void
    {
        $phone = PhoneNumber::find($phoneId);
        if (!$phone) {
            return;
        }
        
        // Dispatch event for test call
        $this->dispatch('initiateTestCall', ['phoneNumber' => $phone->number]);
    }
}