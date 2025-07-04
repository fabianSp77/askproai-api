<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if table doesn't exist (e.g., in test environment)
        if (!Schema::hasTable('retell_agents')) {
            return;
        }
        
        // Transform existing flat configurations to nested structure
        $agents = DB::table('retell_agents')->get();
        
        foreach ($agents as $agent) {
            try {
                $config = json_decode($agent->configuration, true);
                
                if (!$config) {
                    continue;
                }
                
                // Check if already transformed (has nested structure)
                if (isset($config['voice_settings']) || isset($config['conversation_settings'])) {
                    continue; // Skip already transformed configurations
                }
                
                // Transform flat structure to nested structure
                $transformedConfig = $this->transformAgentConfiguration($config);
                
                // Update the configuration
                DB::table('retell_agents')
                    ->where('id', $agent->id)
                    ->update([
                        'configuration' => json_encode($transformedConfig),
                        'updated_at' => now()
                    ]);
                    
                Log::info('Transformed agent configuration', [
                    'agent_id' => $agent->agent_id,
                    'company_id' => $agent->company_id
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to transform agent configuration', [
                    'agent_id' => $agent->agent_id ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as it would lose data organization
        // However, the old flat structure can still be read from the nested structure
    }
    
    /**
     * Transform flat agent configuration to nested structure
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
};