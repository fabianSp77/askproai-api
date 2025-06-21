<?php

namespace App\Services\Config;

use App\Services\RetellV2Service;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RetellConfigValidator
{
    private RetellV2Service $retell;
    private array $requiredEvents = ['call_started', 'call_ended', 'call_analyzed'];
    private int $cacheTTL = 300; // 5 minutes
    
    public function __construct(RetellV2Service $retell)
    {
        $this->retell = $retell;
    }
    
    /**
     * Validate complete agent configuration
     */
    public function validateAgentConfiguration(string $agentId): ConfigValidationResult
    {
        $cacheKey = "retell_config_validation_{$agentId}";
        
        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($agentId) {
            $agent = $this->retell->getAgent($agentId);
            if (!$agent) {
                return new ConfigValidationResult([
                    [
                        'type' => 'critical',
                        'field' => 'agent',
                        'message' => 'Agent not found',
                        'auto_fixable' => false
                    ]
                ]);
            }
            
            $issues = [];
            $warnings = [];
            
            // 1. Webhook URL Check
            $expectedUrl = config('app.url') . '/api/webhooks/retell';
            $currentUrl = $agent['webhook_url'] ?? null;
            
            if ($currentUrl !== $expectedUrl) {
                $issues[] = [
                    'type' => 'critical',
                    'field' => 'webhook_url',
                    'current' => $currentUrl,
                    'expected' => $expectedUrl,
                    'message' => 'Webhook URL ist nicht korrekt konfiguriert',
                    'auto_fixable' => true
                ];
            }
            
            // 2. Webhook Events Check
            $enabledEvents = $agent['webhook_events'] ?? [];
            $missingEvents = array_diff($this->requiredEvents, $enabledEvents);
            
            if (!empty($missingEvents)) {
                $issues[] = [
                    'type' => 'critical',
                    'field' => 'webhook_events',
                    'missing' => $missingEvents,
                    'current' => $enabledEvents,
                    'expected' => $this->requiredEvents,
                    'message' => 'Erforderliche Webhook Events fehlen: ' . implode(', ', $missingEvents),
                    'auto_fixable' => true
                ];
            }
            
            // 3. Custom Functions Check (für MCP)
            $customFunctions = $agent['custom_functions'] ?? [];
            if (empty($customFunctions)) {
                $warnings[] = [
                    'type' => 'warning',
                    'field' => 'custom_functions',
                    'message' => 'Keine MCP Functions konfiguriert - Erweiterte Features nicht verfügbar',
                    'auto_fixable' => true
                ];
            } else {
                // Validate function URLs
                foreach ($customFunctions as $func) {
                    if (isset($func['webhook_url']) && !str_starts_with($func['webhook_url'], config('app.url'))) {
                        $issues[] = [
                            'type' => 'warning',
                            'field' => 'custom_function_url',
                            'function' => $func['name'],
                            'current' => $func['webhook_url'],
                            'message' => "Function URL zeigt nicht auf unser System: {$func['name']}",
                            'auto_fixable' => true
                        ];
                    }
                }
            }
            
            // 4. Voice Configuration Check
            if (empty($agent['voice_id'])) {
                $warnings[] = [
                    'type' => 'warning',
                    'field' => 'voice_id',
                    'message' => 'Keine Stimme konfiguriert',
                    'auto_fixable' => false
                ];
            }
            
            // 5. Language Check for German market
            $language = $agent['language'] ?? 'en-US';
            if (!in_array($language, ['de-DE', 'de-AT', 'de-CH'])) {
                $warnings[] = [
                    'type' => 'warning',
                    'field' => 'language',
                    'current' => $language,
                    'message' => 'Sprache ist nicht auf Deutsch eingestellt',
                    'auto_fixable' => true
                ];
            }
            
            // 6. Prompt Validation
            if (empty($agent['prompt'])) {
                $issues[] = [
                    'type' => 'critical',
                    'field' => 'prompt',
                    'message' => 'Kein Agent Prompt konfiguriert',
                    'auto_fixable' => false
                ];
            }
            
            // 7. Test Webhook Connection (if URL is correct)
            if ($currentUrl === $expectedUrl) {
                $connectionTest = $this->testWebhookConnection($currentUrl);
                if (!$connectionTest['success']) {
                    $issues[] = [
                        'type' => 'critical',
                        'field' => 'webhook_connection',
                        'message' => 'Webhook nicht erreichbar: ' . $connectionTest['error'],
                        'auto_fixable' => false
                    ];
                }
            }
            
            return new ConfigValidationResult($issues, $warnings);
        });
    }
    
    /**
     * Auto-fix configuration issues
     */
    public function autoFixConfiguration(string $agentId, array $issues): array
    {
        $updates = [];
        $fixedIssues = [];
        
        foreach ($issues as $issue) {
            if (!($issue['auto_fixable'] ?? false)) {
                continue;
            }
            
            switch ($issue['field']) {
                case 'webhook_url':
                    $updates['webhook_url'] = $issue['expected'];
                    $fixedIssues[] = 'Webhook URL korrigiert';
                    break;
                    
                case 'webhook_events':
                    $updates['webhook_events'] = $this->requiredEvents;
                    $fixedIssues[] = 'Webhook Events aktiviert';
                    break;
                    
                case 'custom_functions':
                    $updates['custom_functions'] = $this->getMCPFunctions();
                    $fixedIssues[] = 'MCP Functions konfiguriert';
                    break;
                    
                case 'language':
                    $updates['language'] = 'de-DE';
                    $fixedIssues[] = 'Sprache auf Deutsch gesetzt';
                    break;
                    
                case 'custom_function_url':
                    // Fix function URLs
                    if (!isset($updates['custom_functions'])) {
                        $agent = $this->retell->getAgent($agentId);
                        $updates['custom_functions'] = $agent['custom_functions'] ?? [];
                    }
                    foreach ($updates['custom_functions'] as &$func) {
                        if ($func['name'] === $issue['function']) {
                            $func['webhook_url'] = config('app.url') . '/api/mcp/retell/function-call';
                        }
                    }
                    $fixedIssues[] = "Function URL für {$issue['function']} korrigiert";
                    break;
            }
        }
        
        if (!empty($updates)) {
            try {
                $result = $this->retell->updateAgent($agentId, $updates);
                
                // Clear validation cache
                Cache::forget("retell_config_validation_{$agentId}");
                
                Log::info('Retell agent configuration auto-fixed', [
                    'agent_id' => $agentId,
                    'fixes' => $fixedIssues,
                    'updates' => array_keys($updates)
                ]);
                
                return [
                    'success' => true,
                    'fixed_issues' => $fixedIssues,
                    'message' => count($fixedIssues) . ' Probleme wurden behoben'
                ];
            } catch (\Exception $e) {
                Log::error('Failed to auto-fix Retell configuration', [
                    'agent_id' => $agentId,
                    'error' => $e->getMessage()
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Fehler beim Korrigieren: ' . $e->getMessage()
                ];
            }
        }
        
        return [
            'success' => true,
            'message' => 'Keine automatisch behebbaren Probleme gefunden'
        ];
    }
    
    /**
     * Get default MCP functions
     */
    private function getMCPFunctions(): array
    {
        $baseUrl = config('app.url') . '/api/mcp/retell/function-call';
        
        return [
            [
                'name' => 'check_availability',
                'description' => 'Prüfe Verfügbarkeit für einen Termin',
                'webhook_url' => $baseUrl,
                'parameters' => [
                    [
                        'name' => 'date',
                        'type' => 'string',
                        'description' => 'Gewünschtes Datum im Format YYYY-MM-DD'
                    ],
                    [
                        'name' => 'time',
                        'type' => 'string',
                        'description' => 'Gewünschte Zeit im Format HH:MM'
                    ],
                    [
                        'name' => 'service',
                        'type' => 'string',
                        'description' => 'Name des gewünschten Services'
                    ]
                ]
            ],
            [
                'name' => 'book_appointment',
                'description' => 'Buche einen Termin',
                'webhook_url' => $baseUrl,
                'parameters' => [
                    [
                        'name' => 'date',
                        'type' => 'string',
                        'description' => 'Datum im Format YYYY-MM-DD'
                    ],
                    [
                        'name' => 'time',
                        'type' => 'string',
                        'description' => 'Zeit im Format HH:MM'
                    ],
                    [
                        'name' => 'service',
                        'type' => 'string',
                        'description' => 'Service Name'
                    ],
                    [
                        'name' => 'customer_name',
                        'type' => 'string',
                        'description' => 'Name des Kunden'
                    ],
                    [
                        'name' => 'customer_phone',
                        'type' => 'string',
                        'description' => 'Telefonnummer des Kunden'
                    ]
                ]
            ],
            [
                'name' => 'cancel_appointment',
                'description' => 'Storniere einen bestehenden Termin',
                'webhook_url' => $baseUrl,
                'parameters' => [
                    [
                        'name' => 'appointment_id',
                        'type' => 'string',
                        'description' => 'ID des zu stornierenden Termins'
                    ],
                    [
                        'name' => 'reason',
                        'type' => 'string',
                        'description' => 'Grund der Stornierung (optional)'
                    ]
                ]
            ],
            [
                'name' => 'list_services',
                'description' => 'Liste alle verfügbaren Services auf',
                'webhook_url' => $baseUrl,
                'parameters' => []
            ]
        ];
    }
    
    /**
     * Test webhook connection
     */
    private function testWebhookConnection(string $webhookUrl): array
    {
        $testPayload = [
            'event' => 'connection_test',
            'test_id' => uniqid('test_'),
            'timestamp' => now()->toIso8601String(),
            'source' => 'retell_config_validator'
        ];
        
        try {
            $startTime = microtime(true);
            
            $response = Http::timeout(5)
                ->withHeaders([
                    'x-retell-signature' => 'test_signature',
                    'Content-Type' => 'application/json'
                ])
                ->post($webhookUrl, $testPayload);
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response_time_ms' => round($responseTime, 2),
                'body' => $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'type' => get_class($e)
            ];
        }
    }
    
    /**
     * Validate all agents for a company
     */
    public function validateCompanyAgents(int $companyId): array
    {
        $company = \App\Models\Company::find($companyId);
        if (!$company || !$company->retell_api_key) {
            return ['error' => 'Company not found or Retell not configured'];
        }
        
        $agents = $this->retell->listAgents();
        $results = [];
        
        foreach ($agents['agents'] ?? [] as $agent) {
            $validation = $this->validateAgentConfiguration($agent['agent_id']);
            $results[$agent['agent_id']] = [
                'name' => $agent['agent_name'],
                'valid' => $validation->isValid(),
                'issues' => $validation->getIssues(),
                'warnings' => $validation->getWarnings()
            ];
        }
        
        return $results;
    }
}

/**
 * Configuration validation result
 */
class ConfigValidationResult
{
    private array $issues;
    private array $warnings;
    
    public function __construct(array $issues = [], array $warnings = [])
    {
        $this->issues = $issues;
        $this->warnings = $warnings;
    }
    
    public function isValid(): bool
    {
        return empty($this->issues);
    }
    
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }
    
    public function getIssues(): array
    {
        return $this->issues;
    }
    
    public function getWarnings(): array
    {
        return $this->warnings;
    }
    
    public function getCriticalIssues(): array
    {
        return array_filter($this->issues, fn($issue) => $issue['type'] === 'critical');
    }
    
    public function getAutoFixableIssues(): array
    {
        return array_filter($this->issues, fn($issue) => $issue['auto_fixable'] ?? false);
    }
}