<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetellAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'phone_number_id', 'agent_id',
        'name', 'settings', 'active', 'configuration', 'is_active',
        'last_synced_at', 'sync_status'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $casts = [
        'settings' => 'array',
        'configuration' => 'array',
        'active' => 'boolean',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(PhoneNumber::class);
    }
    
    /**
     * Sync agent data from Retell API
     */
    public function syncFromRetell(): bool
    {
        try {
            // Get API key from company
            $company = $this->company;
            if (!$company || !$company->retell_api_key) {
                $this->update(['sync_status' => 'error']);
                return false;
            }
            
            $apiKey = $company->retell_api_key;
            if (strlen($apiKey) > 50) {
                try {
                    $apiKey = decrypt($apiKey);
                } catch (\Exception $e) {
                    // Use as-is if decryption fails
                }
            }
            
            $retellService = new \App\Services\RetellV2Service($apiKey);
            
            // Get full agent configuration
            $agentData = $retellService->getAgent($this->agent_id);
            if (!$agentData) {
                $this->sync_status = 'error';
                $this->save();
                return false;
            }
            
            // Get LLM configuration if using retell-llm
            if (isset($agentData['response_engine']['type']) && 
                $agentData['response_engine']['type'] === 'retell-llm' &&
                isset($agentData['response_engine']['llm_id'])) {
                
                $llmData = $retellService->getRetellLLM($agentData['response_engine']['llm_id']);
                if ($llmData) {
                    $agentData['llm_configuration'] = $llmData;
                }
            }
            
            // Update local data
            $this->update([
                'name' => $agentData['agent_name'] ?? $this->name,
                'configuration' => $agentData,
                'is_active' => ($agentData['status'] ?? 'inactive') === 'active',
                'last_synced_at' => now(),
                'sync_status' => 'synced'
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to sync agent from Retell', [
                'agent_id' => $this->agent_id,
                'error' => $e->getMessage()
            ]);
            
            $this->update(['sync_status' => 'error']);
            return false;
        }
    }
    
    /**
     * Push local changes to Retell
     */
    public function pushToRetell(array $changes = []): bool
    {
        try {
            // Get API key from company
            $company = $this->company;
            if (!$company || !$company->retell_api_key) {
                $this->update(['sync_status' => 'error']);
                return false;
            }
            
            $apiKey = $company->retell_api_key;
            if (strlen($apiKey) > 50) {
                try {
                    $apiKey = decrypt($apiKey);
                } catch (\Exception $e) {
                    // Use as-is if decryption fails
                }
            }
            
            $retellService = new \App\Services\RetellV2Service($apiKey);
            
            $updateData = $changes ?: $this->configuration;
            
            // Update agent
            $result = $retellService->updateAgent($this->agent_id, $updateData);
            
            if ($result) {
                $this->update([
                    'last_synced_at' => now(),
                    'sync_status' => 'synced'
                ]);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            \Log::error('Failed to push agent to Retell', [
                'agent_id' => $this->agent_id,
                'error' => $e->getMessage()
            ]);
            
            $this->update(['sync_status' => 'error']);
            return false;
        }
    }
    
    /**
     * Get voice settings from configuration
     */
    public function getVoiceSettings(): array
    {
        return [
            'voice_id' => $this->configuration['voice_id'] ?? null,
            'voice_speed' => $this->configuration['voice_speed'] ?? 1.0,
            'voice_temperature' => $this->configuration['voice_temperature'] ?? 0.7,
            'language' => $this->configuration['language'] ?? 'de-DE',
        ];
    }
    
    /**
     * Get function count from configuration
     */
    public function getFunctionCount(): int
    {
        if (!isset($this->configuration['llm_configuration']['general_tools'])) {
            return 0;
        }
        
        return count($this->configuration['llm_configuration']['general_tools']);
    }
    
    /**
     * Check if agent needs sync
     */
    public function needsSync(): bool
    {
        if (!$this->last_synced_at) {
            return true;
        }
        
        // Sync if older than 1 hour
        return $this->last_synced_at->lt(now()->subHour());
    }
}
