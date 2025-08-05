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
        'last_synced_at', 'sync_status', 'version', 'version_title', 'is_published'
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
     * Get available agent types
     */
    public static function getTypes(): array
    {
        return [
            'inbound' => 'Inbound',
            'outbound' => 'Outbound',
            'both' => 'Both',
        ];
    }
    
    /**
     * Get supported languages
     */
    public static function getSupportedLanguages(): array
    {
        return [
            'de' => 'Deutsch',
            'en' => 'English',
            'es' => 'Español',
            'fr' => 'Français',
            'it' => 'Italiano',
        ];
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
            
            // Store raw API data without transformation
            // This ensures the data matches exactly what's in Retell.ai
            
            // Update local data
            $this->update([
                'name' => $agentData['agent_name'] ?? $this->name,
                'configuration' => $agentData, // Store raw API response
                'is_active' => ($agentData['status'] ?? 'inactive') === 'active',
                'last_synced_at' => now(),
                'sync_status' => 'synced',
                // Update version fields if present
                'version' => $agentData['version'] ?? null,
                'version_title' => $agentData['version_title'] ?? null,
                'is_published' => $agentData['is_published'] ?? false
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
    
    /**
     * Transform flat agent configuration to nested structure for UI consistency
     */
    protected function transformAgentConfiguration(array $agentDetails): array
    {
        // Start with the original data
        $transformed = $agentDetails;
        
        // Create voice_settings object
        $transformed['voice_settings'] = [
            'voice_id' => $agentDetails['voice_id'] ?? '',
            'voice_model' => $agentDetails['voice_model'] ?? '',
            'voice_temperature' => $agentDetails['voice_temperature'] ?? 0.2,
            'voice_speed' => $agentDetails['voice_speed'] ?? 1.0,
            'volume' => $agentDetails['volume'] ?? 1.0,
            'stability' => $agentDetails['stability'] ?? 0.5,
            'similarity_boost' => $agentDetails['similarity_boost'] ?? 0.75
        ];
        
        // Create conversation_settings object
        $transformed['conversation_settings'] = [
            'language' => $agentDetails['language'] ?? 'en-US',
            'enable_backchannel' => $agentDetails['enable_backchannel'] ?? true,
            'interruption_sensitivity' => $agentDetails['interruption_sensitivity'] ?? 1,
            'responsiveness' => $agentDetails['responsiveness'] ?? 1,
            'boosted_keywords' => $agentDetails['boosted_keywords'] ?? [],
            'reminder_trigger_ms' => $agentDetails['reminder_trigger_ms'] ?? 10000,
            'reminder_max_count' => $agentDetails['reminder_max_count'] ?? 1,
            'normalize_for_speech' => $agentDetails['normalize_for_speech'] ?? true,
            'pronunciation_guide' => $agentDetails['pronunciation_guide'] ?? [],
            'opt_out_sensitive_data_storage' => $agentDetails['opt_out_sensitive_data_storage'] ?? false
        ];
        
        // Create audio_settings object
        $transformed['audio_settings'] = [
            'ambient_sound' => $agentDetails['ambient_sound'] ?? null,
            'ambient_sound_volume' => $agentDetails['ambient_sound_volume'] ?? 0.5,
            'backchannel_frequency' => $agentDetails['backchannel_frequency'] ?? 0.8,
            'backchannel_words' => $agentDetails['backchannel_words'] ?? []
        ];
        
        // Create analysis_settings object
        $transformed['analysis_settings'] = [
            'track_user_sentiment' => $agentDetails['track_user_sentiment'] ?? false,
            'track_agent_sentiment' => $agentDetails['track_agent_sentiment'] ?? false,
            'detect_keywords' => $agentDetails['detect_keywords'] ?? [],
            'custom_keywords' => $agentDetails['custom_keywords'] ?? []
        ];
        
        // Create end_call_settings object
        $transformed['end_call_settings'] = [
            'end_call_after_silence_ms' => $agentDetails['end_call_after_silence_ms'] ?? 30000,
            'max_call_duration_ms' => $agentDetails['max_call_duration_ms'] ?? 3600000,
            'end_call_phrases' => $agentDetails['end_call_phrases'] ?? []
        ];
        
        // Create voicemail_settings object
        $transformed['voicemail_settings'] = [
            'enable_voicemail_detection' => $agentDetails['enable_voicemail_detection'] ?? false,
            'voicemail_message' => $agentDetails['voicemail_message'] ?? '',
            'voicemail_detection_timeout_ms' => $agentDetails['voicemail_detection_timeout_ms'] ?? 5000
        ];
        
        // Create webhook_settings object
        $transformed['webhook_settings'] = [
            'webhook_url' => $agentDetails['webhook_url'] ?? '',
            'enable_webhook_for_analysis' => $agentDetails['enable_webhook_for_analysis'] ?? true,
            'enable_webhook_for_transcripts' => $agentDetails['enable_webhook_for_transcripts'] ?? true
        ];
        
        // Create llm_settings object if response_engine is retell-llm
        if (isset($agentDetails['response_engine']['type']) && 
            $agentDetails['response_engine']['type'] === 'retell-llm') {
            
            $transformed['llm_settings'] = [
                'llm_id' => $agentDetails['response_engine']['llm_id'] ?? '',
                'model' => $agentDetails['response_engine']['model'] ?? 'gpt-4',
                'temperature' => $agentDetails['response_engine']['temperature'] ?? 0.7,
                'max_tokens' => $agentDetails['response_engine']['max_tokens'] ?? 150,
                'system_prompt' => $agentDetails['response_engine']['system_prompt'] ?? ''
            ];
            
            // Include LLM configuration if available
            if (isset($agentDetails['llm_configuration'])) {
                $transformed['llm_settings']['configuration'] = $agentDetails['llm_configuration'];
            }
        }
        
        return $transformed;
    }
}
