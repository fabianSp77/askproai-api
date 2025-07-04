<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AgentConfigurationProcessor
{
    /**
     * Process agent configuration based on predefined rules
     */
    public function processConfiguration(array $config, array $modifications = []): array
    {
        // Apply standard modifications
        $config = $this->applyStandardModifications($config);
        
        // Apply custom modifications
        foreach ($modifications as $key => $value) {
            $config = $this->setNestedValue($config, $key, $value);
        }
        
        // Validate and fix configuration
        $config = $this->validateAndFixConfiguration($config);
        
        return $config;
    }
    
    /**
     * Apply standard modifications for AskProAI agents
     */
    protected function applyStandardModifications(array $config): array
    {
        // Ensure webhook is set to AskProAI
        if (!isset($config['webhook_settings']) || empty($config['webhook_settings']['url'])) {
            $config['webhook_settings'] = [
                'url' => 'https://api.askproai.de/api/retell/webhook',
                'listening_events' => [
                    'call_started',
                    'call_ended',
                    'call_analyzed'
                ]
            ];
        }
        
        // Ensure German language for German market
        if (!isset($config['language']) || $config['language'] !== 'de') {
            $config['language'] = 'de';
        }
        
        // Set optimal voice settings for German
        if (isset($config['voice_id']) && str_starts_with($config['voice_id'], 'elevenlabs-')) {
            // ElevenLabs optimal settings for German
            $config['voice_temperature'] = $config['voice_temperature'] ?? 0.7;
            $config['voice_speed'] = $config['voice_speed'] ?? 1.0;
        }
        
        // Ensure response speed is optimal
        if (!isset($config['response_speed'])) {
            $config['response_speed'] = 1000; // 1 second
        }
        
        // Set interruption sensitivity for natural conversation
        if (!isset($config['interruption_sensitivity'])) {
            $config['interruption_sensitivity'] = 0.7;
        }
        
        // Enable backchannel for natural flow
        if (!isset($config['enable_backchannel'])) {
            $config['enable_backchannel'] = true;
        }
        
        // Set optimal end call settings
        if (!isset($config['end_call_after_silence_ms'])) {
            $config['end_call_after_silence_ms'] = 20000; // 20 seconds
        }
        
        return $config;
    }
    
    /**
     * Validate and fix configuration issues
     */
    protected function validateAndFixConfiguration(array $config): array
    {
        // Ensure required fields exist
        $requiredFields = [
            'agent_name' => 'AskProAI Agent',
            'voice_id' => 'elevenlabs-Matilda',
            'language' => 'de',
            'response_engine' => ['type' => 'retell-llm']
        ];
        
        foreach ($requiredFields as $field => $default) {
            if (!isset($config[$field])) {
                $config[$field] = $default;
            }
        }
        
        // Fix response engine structure if needed
        if (isset($config['response_engine'])) {
            // Ensure type is set
            if (!isset($config['response_engine']['type'])) {
                $config['response_engine']['type'] = 'retell-llm';
            }
            
            // If using retell-llm, ensure llm_id or model is set
            if ($config['response_engine']['type'] === 'retell-llm') {
                if (!isset($config['response_engine']['llm_id']) && 
                    !isset($config['response_engine']['model'])) {
                    // Default to GPT-4 for best results
                    $config['response_engine']['model'] = 'gpt-4';
                }
            }
        }
        
        // Validate voice settings ranges
        if (isset($config['voice_temperature'])) {
            $config['voice_temperature'] = max(0, min(2, $config['voice_temperature']));
        }
        
        if (isset($config['voice_speed'])) {
            $config['voice_speed'] = max(0.5, min(2, $config['voice_speed']));
        }
        
        if (isset($config['interruption_sensitivity'])) {
            $config['interruption_sensitivity'] = max(0, min(1, $config['interruption_sensitivity']));
        }
        
        return $config;
    }
    
    /**
     * Set nested value in array using dot notation
     */
    protected function setNestedValue(array $array, string $key, $value): array
    {
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }
            
            // If the key doesn't exist, create it
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            
            if ($i < count($keys) - 1) {
                $current = &$current[$key];
            }
        }
        
        $current[end($keys)] = $value;
        
        return $array;
    }
    
    /**
     * Process functions/tools configuration
     */
    public function processFunctions(array $functions, array $options = []): array
    {
        $processed = [];
        
        foreach ($functions as $function) {
            // Skip if function should be excluded
            if (isset($options['exclude']) && in_array($function['name'] ?? '', $options['exclude'])) {
                continue;
            }
            
            // Process function based on type
            if ($this->isCalcomFunction($function)) {
                $function = $this->processCalcomFunction($function);
            } elseif ($this->isDatabaseFunction($function)) {
                $function = $this->processDatabaseFunction($function);
            }
            
            // Apply any custom modifications
            if (isset($options['modifications'][$function['name'] ?? ''])) {
                $function = array_merge($function, $options['modifications'][$function['name']]);
            }
            
            $processed[] = $function;
        }
        
        // Add any additional functions
        if (isset($options['add'])) {
            foreach ($options['add'] as $newFunction) {
                $processed[] = $newFunction;
            }
        }
        
        return $processed;
    }
    
    /**
     * Check if function is Cal.com related
     */
    protected function isCalcomFunction(array $function): bool
    {
        $name = strtolower($function['name'] ?? '');
        $url = strtolower($function['url'] ?? '');
        
        return str_contains($name, 'cal') || 
               str_contains($name, 'appointment') || 
               str_contains($name, 'booking') ||
               str_contains($url, 'cal') ||
               str_contains($url, 'appointment');
    }
    
    /**
     * Process Cal.com function
     */
    protected function processCalcomFunction(array $function): array
    {
        // Ensure URL points to AskProAI MCP endpoint
        if (isset($function['url']) && !str_contains($function['url'], 'askproai.de')) {
            $function['url'] = str_replace(
                ['http://localhost', 'https://localhost', 'http://127.0.0.1', 'https://127.0.0.1'],
                'https://api.askproai.de',
                $function['url']
            );
        }
        
        // Ensure proper authentication headers
        if (!isset($function['headers'])) {
            $function['headers'] = [];
        }
        
        // Add standard headers
        $function['headers']['Content-Type'] = 'application/json';
        $function['headers']['Accept'] = 'application/json';
        
        return $function;
    }
    
    /**
     * Check if function is database related
     */
    protected function isDatabaseFunction(array $function): bool
    {
        $name = strtolower($function['name'] ?? '');
        $url = strtolower($function['url'] ?? '');
        
        return str_contains($name, 'database') || 
               str_contains($name, 'customer') || 
               str_contains($name, 'query') ||
               str_contains($url, 'database') ||
               str_contains($url, 'customer');
    }
    
    /**
     * Process database function
     */
    protected function processDatabaseFunction(array $function): array
    {
        // Similar processing as Cal.com functions
        return $this->processCalcomFunction($function);
    }
    
    /**
     * Generate prompt enhancement for agent
     */
    public function enhancePrompt(string $basePrompt, array $options = []): string
    {
        $enhancements = [];
        
        // Add language instruction if German
        if (($options['language'] ?? 'de') === 'de') {
            $enhancements[] = "WICHTIG: Führe ALLE Gespräche ausschließlich auf Deutsch. Antworte NIEMALS auf Englisch, auch wenn der Anrufer Englisch spricht.";
        }
        
        // Add appointment booking context
        if ($options['include_booking_context'] ?? true) {
            $enhancements[] = "Du bist ein KI-Assistent für Terminbuchungen. Deine Hauptaufgabe ist es, Anrufer bei der Terminvereinbarung zu unterstützen.";
        }
        
        // Add professional tone
        if ($options['professional_tone'] ?? true) {
            $enhancements[] = "Sei stets höflich, professionell und hilfsbereit. Verwende die Sie-Form, es sei denn, der Anrufer bietet das Du an.";
        }
        
        // Add data collection reminder
        if ($options['collect_data'] ?? true) {
            $enhancements[] = "Erfasse immer: Name, Telefonnummer, gewünschte Dienstleistung und Terminwunsch des Anrufers.";
        }
        
        // Combine enhancements with base prompt
        if (!empty($enhancements)) {
            return implode("\n\n", $enhancements) . "\n\n" . $basePrompt;
        }
        
        return $basePrompt;
    }
}