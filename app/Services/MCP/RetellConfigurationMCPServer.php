<?php

namespace App\Services\MCP;

use App\Models\Company;
use App\Models\RetellConfiguration;
use App\Services\RetellService;
use App\Exceptions\MCPException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * MCP Server for Retell.ai configuration management
 * 
 * This server handles all Retell configuration through the UI,
 * eliminating the need for direct API access from the frontend.
 */
class RetellConfigurationMCPServer
{
    protected array $config;
    
    public function __construct()
    {
        $this->config = [
            'cache_ttl' => 300, // 5 minutes
            'test_timeout' => 10,
        ];
    }
    
    /**
     * Get current webhook configuration
     * 
     * @param array $params ['company_id' => int]
     * @return array
     */
    public function getWebhook(array $params): array
    {
        $this->validateParams($params, ['company_id']);
        
        $company = Company::findOrFail($params['company_id']);
        
        // Get or create configuration
        $config = RetellConfiguration::firstOrCreate(
            ['company_id' => $company->id],
            [
                'webhook_url' => url('/api/webhooks/retell'),
                'webhook_secret' => Str::random(32),
                'webhook_events' => ['call_started', 'call_ended', 'call_analyzed'],
                'custom_functions' => $this->getDefaultCustomFunctions(),
            ]
        );
        
        return [
            'webhook_url' => $config->webhook_url,
            'webhook_secret' => $config->webhook_secret,
            'webhook_events' => $config->webhook_events,
            'last_tested_at' => $config->last_tested_at,
            'test_status' => $config->test_status,
            'is_configured_in_retell' => $this->checkRetellConfiguration($company, $config),
        ];
    }
    
    /**
     * Update webhook configuration
     * 
     * @param array $params
     * @return array
     */
    public function updateWebhook(array $params): array
    {
        $this->validateParams($params, ['company_id']);
        
        $company = Company::findOrFail($params['company_id']);
        $config = RetellConfiguration::where('company_id', $company->id)->firstOrFail();
        
        // Update local configuration
        if (isset($params['webhook_events'])) {
            $config->webhook_events = $params['webhook_events'];
        }
        
        if (isset($params['regenerate_secret']) && $params['regenerate_secret']) {
            $config->webhook_secret = Str::random(32);
        }
        
        $config->save();
        
        // Clear cache
        $this->clearCache($company->id);
        
        Log::info('Webhook configuration updated', [
            'company_id' => $company->id,
            'events' => $config->webhook_events,
        ]);
        
        return [
            'success' => true,
            'webhook_url' => $config->webhook_url,
            'webhook_secret' => $config->webhook_secret,
            'webhook_events' => $config->webhook_events,
            'message' => 'Konfiguration aktualisiert. Bitte aktualisieren Sie die Webhook-URL in Retell.ai.',
        ];
    }
    
    /**
     * Test webhook configuration
     * 
     * @param array $params ['company_id' => int]
     * @return array
     */
    public function testWebhook(array $params): array
    {
        $this->validateParams($params, ['company_id']);
        
        $company = Company::findOrFail($params['company_id']);
        $config = RetellConfiguration::where('company_id', $company->id)->firstOrFail();
        
        try {
            // Create test payload
            $testPayload = [
                'event' => 'test',
                'call_id' => 'test_' . Str::uuid(),
                'timestamp' => now()->toIso8601String(),
                'test_mode' => true,
            ];
            
            // Generate signature
            $signature = $this->generateWebhookSignature($testPayload, $config->webhook_secret);
            
            // Send test request
            $response = Http::timeout($this->config['test_timeout'])
                ->withHeaders([
                    'X-Retell-Signature' => $signature,
                    'Content-Type' => 'application/json',
                ])
                ->post($config->webhook_url, $testPayload);
            
            $success = $response->successful();
            
            // Update test status
            $config->update([
                'last_tested_at' => now(),
                'test_status' => $success ? 'success' : 'failed',
            ]);
            
            return [
                'success' => $success,
                'status_code' => $response->status(),
                'response_time_ms' => $response->transferStats?->getTransferTime() * 1000,
                'message' => $success 
                    ? 'Webhook erfolgreich getestet' 
                    : 'Webhook-Test fehlgeschlagen: ' . $response->status(),
            ];
            
        } catch (\Exception $e) {
            $config->update([
                'last_tested_at' => now(),
                'test_status' => 'failed',
            ]);
            
            Log::error('Webhook test failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => 'Test fehlgeschlagen: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get custom functions configuration
     * 
     * @param array $params ['company_id' => int]
     * @return array
     */
    public function getCustomFunctions(array $params): array
    {
        $this->validateParams($params, ['company_id']);
        
        $company = Company::findOrFail($params['company_id']);
        $config = RetellConfiguration::where('company_id', $company->id)->first();
        
        if (!$config) {
            return ['custom_functions' => $this->getDefaultCustomFunctions()];
        }
        
        return [
            'custom_functions' => $config->custom_functions ?? $this->getDefaultCustomFunctions(),
            'base_url' => url('/api/mcp/gateway/retell/functions'),
        ];
    }
    
    /**
     * Update custom function configuration
     * 
     * @param array $params
     * @return array
     */
    public function updateCustomFunction(array $params): array
    {
        $this->validateParams($params, ['company_id', 'function_name']);
        
        $company = Company::findOrFail($params['company_id']);
        $config = RetellConfiguration::where('company_id', $company->id)->firstOrFail();
        
        $functions = $config->custom_functions ?? [];
        $functionName = $params['function_name'];
        
        // Find and update function
        $found = false;
        foreach ($functions as &$function) {
            if ($function['name'] === $functionName) {
                if (isset($params['enabled'])) {
                    $function['enabled'] = $params['enabled'];
                }
                if (isset($params['description'])) {
                    $function['description'] = $params['description'];
                }
                if (isset($params['parameters'])) {
                    $function['parameters'] = $params['parameters'];
                }
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            throw new MCPException("Function '{$functionName}' not found", MCPException::INVALID_PARAMS);
        }
        
        $config->custom_functions = $functions;
        $config->save();
        
        // Clear cache
        $this->clearCache($company->id);
        
        return [
            'success' => true,
            'function' => $function,
            'message' => "Custom Function '{$functionName}' aktualisiert",
        ];
    }
    
    /**
     * Deploy custom functions to Retell.ai
     * 
     * @param array $params ['company_id' => int]
     * @return array
     */
    public function deployCustomFunctions(array $params): array
    {
        $this->validateParams($params, ['company_id']);
        
        $company = Company::findOrFail($params['company_id']);
        
        if (!$company->retell_api_key) {
            throw new MCPException('Retell API key not configured', MCPException::RETELL_API_ERROR);
        }
        
        $config = RetellConfiguration::where('company_id', $company->id)->firstOrFail();
        $functions = collect($config->custom_functions)->where('enabled', true)->values();
        
        try {
            $retellService = new RetellService(decrypt($company->retell_api_key));
            
            // Get current agent configuration
            $agent = $retellService->getAgent($company->retell_agent_id);
            if (!$agent) {
                throw new MCPException('Agent not found in Retell', MCPException::RETELL_API_ERROR);
            }
            
            // Update agent with custom functions
            $agentUpdate = [
                'custom_functions' => $functions->map(function ($func) {
                    return [
                        'name' => $func['name'],
                        'description' => $func['description'],
                        'url' => url("/api/mcp/gateway/retell/functions/{$func['name']}"),
                        'method' => 'POST',
                        'parameters' => $func['parameters'],
                    ];
                })->toArray(),
            ];
            
            $updated = $retellService->updateAgent($company->retell_agent_id, $agentUpdate);
            
            if (!$updated) {
                throw new MCPException('Failed to update agent', MCPException::RETELL_API_ERROR);
            }
            
            Log::info('Custom functions deployed to Retell', [
                'company_id' => $company->id,
                'agent_id' => $company->retell_agent_id,
                'functions' => $functions->pluck('name'),
            ]);
            
            return [
                'success' => true,
                'deployed_functions' => $functions->pluck('name'),
                'message' => 'Custom Functions erfolgreich zu Retell.ai deployed',
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to deploy custom functions', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            
            throw new MCPException(
                'Deployment fehlgeschlagen: ' . $e->getMessage(),
                MCPException::RETELL_API_ERROR
            );
        }
    }
    
    /**
     * Get Retell agent prompt template
     * 
     * @param array $params ['company_id' => int]
     * @return array
     */
    public function getAgentPromptTemplate(array $params): array
    {
        $this->validateParams($params, ['company_id']);
        
        $company = Company::findOrFail($params['company_id']);
        
        $template = $this->generateAgentPromptTemplate($company);
        
        return [
            'prompt_template' => $template,
            'variables' => [
                'company_name' => $company->name,
                'services' => $company->services()->pluck('name')->join(', '),
                'working_hours' => $this->formatWorkingHours($company),
            ],
        ];
    }
    
    /**
     * Check if webhook is configured in Retell
     */
    protected function checkRetellConfiguration(Company $company, RetellConfiguration $config): bool
    {
        if (!$company->retell_api_key || !$company->retell_agent_id) {
            return false;
        }
        
        $cacheKey = "retell_config_check:{$company->id}";
        
        return Cache::remember($cacheKey, 60, function () use ($company) {
            try {
                $retellService = new RetellService(decrypt($company->retell_api_key));
                $agent = $retellService->getAgent($company->retell_agent_id);
                
                // Check if webhook URL is configured
                return isset($agent['webhook_url']) && 
                       $agent['webhook_url'] === url('/api/webhooks/retell');
                       
            } catch (\Exception $e) {
                Log::warning('Failed to check Retell configuration', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        });
    }
    
    /**
     * Get default custom functions
     */
    protected function getDefaultCustomFunctions(): array
    {
        return [
            [
                'name' => 'collect_appointment',
                'enabled' => true,
                'description' => 'Sammelt Termindaten vom Anrufer',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'datum' => ['type' => 'string', 'description' => 'Gewünschtes Datum'],
                        'uhrzeit' => ['type' => 'string', 'description' => 'Gewünschte Uhrzeit'],
                        'dienstleistung' => ['type' => 'string', 'description' => 'Gewünschte Dienstleistung'],
                        'name' => ['type' => 'string', 'description' => 'Name des Kunden'],
                        'telefonnummer' => ['type' => 'string', 'description' => 'Telefonnummer des Kunden'],
                    ],
                    'required' => ['datum', 'uhrzeit', 'dienstleistung', 'name', 'telefonnummer'],
                ],
            ],
            [
                'name' => 'change_appointment',
                'enabled' => true,
                'description' => 'Ändert einen bestehenden Termin',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'neues_datum' => ['type' => 'string', 'description' => 'Neues Datum'],
                        'neue_uhrzeit' => ['type' => 'string', 'description' => 'Neue Uhrzeit'],
                    ],
                    'required' => ['neues_datum', 'neue_uhrzeit'],
                ],
            ],
            [
                'name' => 'cancel_appointment',
                'enabled' => true,
                'description' => 'Storniert einen bestehenden Termin',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'grund' => ['type' => 'string', 'description' => 'Stornierungsgrund (optional)'],
                    ],
                ],
            ],
            [
                'name' => 'check_availability',
                'enabled' => true,
                'description' => 'Prüft verfügbare Termine',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'datum' => ['type' => 'string', 'description' => 'Zu prüfendes Datum'],
                        'dienstleistung' => ['type' => 'string', 'description' => 'Gewünschte Dienstleistung'],
                    ],
                    'required' => ['datum'],
                ],
            ],
        ];
    }
    
    /**
     * Generate webhook signature
     */
    protected function generateWebhookSignature(array $payload, string $secret): string
    {
        $payloadString = json_encode($payload);
        return hash_hmac('sha256', $payloadString, $secret);
    }
    
    /**
     * Generate agent prompt template
     */
    protected function generateAgentPromptTemplate(Company $company): string
    {
        return <<<EOT
Du bist ein freundlicher KI-Assistent für {$company->name}.

Deine Hauptaufgaben:
1. Begrüße Anrufer freundlich mit dem Firmennamen
2. Erfasse Terminwünsche und nutze die collect_appointment Funktion
3. Bei Terminänderungen nutze change_appointment
4. Bei Stornierungen nutze cancel_appointment
5. Prüfe Verfügbarkeiten mit check_availability

Wichtige Informationen:
- Firmenname: {$company->name}
- Angebotene Dienstleistungen: {services}
- Öffnungszeiten: {working_hours}

Verhalte dich stets professionell, freundlich und hilfsbereit.
Bestätige alle erfassten Daten bevor du eine Funktion aufrufst.
EOT;
    }
    
    /**
     * Format working hours for display
     */
    protected function formatWorkingHours(Company $company): string
    {
        // This would fetch actual working hours from the database
        // For now, return a placeholder
        return "Montag-Freitag 9:00-18:00 Uhr";
    }
    
    /**
     * Validate required parameters
     */
    protected function validateParams(array $params, array $required): void
    {
        foreach ($required as $param) {
            if (!isset($params[$param])) {
                throw MCPException::validationError([
                    $param => ["The {$param} field is required."]
                ]);
            }
        }
    }
    
    /**
     * Clear cache for company
     */
    protected function clearCache(int $companyId): void
    {
        Cache::forget("retell_config_check:{$companyId}");
    }
    
    /**
     * Health check
     */
    public function health(): array
    {
        return [
            'status' => 'healthy',
            'service' => 'RetellConfigurationMCPServer',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}