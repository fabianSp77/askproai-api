<?php

namespace App\Services\Provisioning;

use App\Models\Branch;
use App\Models\Company;
use App\Services\RetellV2Service;
use App\Services\Logging\ProductionLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RetellAgentProvisioner
{
    private ProductionLogger $logger;
    
    public function __construct()
    {
        $this->logger = new ProductionLogger();
    }
    
    /**
     * Create Retell AI agent for a branch
     */
    public function createAgentForBranch(Branch $branch): array
    {
        $this->logger->logApiCall('RetellProvisioner', 'createAgentForBranch', [
            'branch_id' => $branch->id,
            'company_id' => $branch->company_id,
        ], null, 0);
        
        try {
            // Validate prerequisites
            $this->validateBranch($branch);
            
            // Get or create API key
            $apiKey = $this->getRetellApiKey($branch->company);
            if (!$apiKey) {
                throw new \Exception('No Retell API key configured for company');
            }
            
            // Initialize service
            $retellService = new RetellV2Service($apiKey);
            
            // Generate agent configuration
            $agentConfig = $this->generateAgentConfig($branch);
            
            // Create agent via API
            $agent = $retellService->createAgent($agentConfig);
            
            if (!$agent || !isset($agent['agent_id'])) {
                throw new \Exception('Failed to create agent - invalid response');
            }
            
            // Store agent information
            $this->storeAgentInfo($branch, $agent);
            
            // Configure phone number if available
            if ($branch->phone_number) {
                $this->assignPhoneNumber($branch, $agent['agent_id'], $apiKey);
            }
            
            // Cache agent info for quick access
            $this->cacheAgentInfo($branch, $agent);
            
            $this->logger->logApiCall('RetellProvisioner', 'createAgentForBranch', [
                'branch_id' => $branch->id,
                'agent_id' => $agent['agent_id'],
            ], $agent, microtime(true));
            
            return [
                'success' => true,
                'agent_id' => $agent['agent_id'],
                'agent' => $agent,
                'message' => 'Agent erfolgreich erstellt',
            ];
            
        } catch (\Exception $e) {
            $this->logger->logError($e, [
                'method' => 'createAgentForBranch',
                'branch_id' => $branch->id,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Fehler beim Erstellen des Agents',
            ];
        }
    }
    
    /**
     * Update existing agent configuration
     */
    public function updateAgentForBranch(Branch $branch): array
    {
        if (!$branch->retell_agent_id) {
            return $this->createAgentForBranch($branch);
        }
        
        try {
            $apiKey = $this->getRetellApiKey($branch->company);
            $retellService = new RetellV2Service($apiKey);
            
            // Generate updated configuration
            $agentConfig = $this->generateAgentConfig($branch);
            
            // Update agent
            $result = $retellService->updateAgent($branch->retell_agent_id, $agentConfig);
            
            // Clear cache
            Cache::forget("retell_agent_{$branch->id}");
            
            return [
                'success' => true,
                'agent_id' => $branch->retell_agent_id,
                'message' => 'Agent erfolgreich aktualisiert',
            ];
            
        } catch (\Exception $e) {
            $this->logger->logError($e, [
                'method' => 'updateAgentForBranch',
                'branch_id' => $branch->id,
                'agent_id' => $branch->retell_agent_id,
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Generate agent configuration based on branch settings
     */
    private function generateAgentConfig(Branch $branch): array
    {
        $company = $branch->company;
        
        // Base configuration
        $config = [
            'agent_name' => "{$company->name} - {$branch->name}",
            'response_engine' => [
                'type' => 'retell-llm',
                'llm_id' => 'gpt-4',
                'system_prompt' => $this->generatePrompt($branch),
            ],
            'voice_id' => $branch->getSetting('voice_id', 'de-DE-FlorianNeural'),
            'voice_speed' => 1.0,
            'voice_temperature' => 0.5,
            'language' => $branch->getSetting('language', 'de-DE'),
            'interruption_sensitivity' => 0.5,
            'enable_backchannel' => true,
            'ambient_sound' => null,
            'webhook_url' => route('webhook.unified'),
            'fallback_webhook_url' => null,
            'end_call_after_silence_ms' => 10000,
            'max_call_duration_ms' => 1800000, // 30 minutes
            'custom_keywords' => $this->getBookedKeywords($branch),
            'custom_functions' => $this->getAgentFunctions($branch),
        ];
        
        // Add custom settings if available
        $customSettings = $branch->getSetting('retell_custom_settings', []);
        if (!empty($customSettings)) {
            $config = array_merge($config, $customSettings);
        }
        
        return $config;
    }
    
    /**
     * Generate dynamic prompt based on branch and services
     */
    private function generatePrompt(Branch $branch): string
    {
        $company = $branch->company;
        $services = $branch->services->map(function($service) {
            return "- {$service->name} ({$service->duration} Minuten): {$service->price}€";
        })->join("\n");
        
        $staff = $branch->staff->map(function($staff) {
            return "- {$staff->name} ({$staff->title})";
        })->join("\n");
        
        $businessHours = $this->formatBusinessHours($branch);
        
        $prompt = <<<EOT
Du bist der KI-Assistent für {$company->name}, Filiale {$branch->name}.

DEINE ROLLE:
- Freundlicher und professioneller Empfang am Telefon
- Terminvereinbarung für Kunden
- Beantwortung von Fragen zu Services und Öffnungszeiten

UNTERNEHMENSINFORMATIONEN:
- Firma: {$company->name}
- Filiale: {$branch->name}
- Adresse: {$branch->address}, {$branch->postal_code} {$branch->city}
- Telefon: {$branch->phone_number}

VERFÜGBARE SERVICES:
{$services}

MITARBEITER:
{$staff}

ÖFFNUNGSZEITEN:
{$businessHours}

WICHTIGE REGELN:
1. Sei immer höflich und zuvorkommend
2. Verwende Siezen, außer der Kunde bietet das Du an
3. Frage nach allen notwendigen Informationen für die Terminbuchung:
   - Name des Kunden
   - Telefonnummer
   - Gewünschter Service
   - Bevorzugter Mitarbeiter (optional)
   - Terminwunsch (Datum und Uhrzeit)
4. Bestätige alle Informationen bevor du den Termin buchst
5. Informiere über die Stornierungsbedingungen
6. Verabschiede dich freundlich

SPEZIELLE ANWEISUNGEN:
- Sprache: Deutsch
- Dialekt: Hochdeutsch
- Ton: Professionell aber herzlich
EOT;

        // Add custom instructions if available
        $customInstructions = $branch->getSetting('custom_prompt_instructions');
        if ($customInstructions) {
            $prompt .= "\n\nZUSÄTZLICHE ANWEISUNGEN:\n" . $customInstructions;
        }
        
        return $prompt;
    }
    
    /**
     * Get agent functions for appointment booking
     */
    private function getAgentFunctions(Branch $branch): array
    {
        return [
            [
                'name' => 'check_availability',
                'description' => 'Verfügbarkeit für einen Service prüfen',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'service_id' => [
                            'type' => 'integer',
                            'description' => 'ID des gewünschten Services',
                        ],
                        'date' => [
                            'type' => 'string',
                            'description' => 'Gewünschtes Datum (YYYY-MM-DD)',
                        ],
                        'staff_id' => [
                            'type' => 'integer',
                            'description' => 'ID des gewünschten Mitarbeiters (optional)',
                        ],
                    ],
                    'required' => ['service_id', 'date'],
                ],
            ],
            [
                'name' => 'book_appointment',
                'description' => 'Termin buchen',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_name' => [
                            'type' => 'string',
                            'description' => 'Name des Kunden',
                        ],
                        'customer_phone' => [
                            'type' => 'string',
                            'description' => 'Telefonnummer des Kunden',
                        ],
                        'customer_email' => [
                            'type' => 'string',
                            'description' => 'E-Mail des Kunden (optional)',
                        ],
                        'service_id' => [
                            'type' => 'integer',
                            'description' => 'ID des Services',
                        ],
                        'staff_id' => [
                            'type' => 'integer',
                            'description' => 'ID des Mitarbeiters',
                        ],
                        'date' => [
                            'type' => 'string',
                            'description' => 'Datum (YYYY-MM-DD)',
                        ],
                        'time' => [
                            'type' => 'string',
                            'description' => 'Uhrzeit (HH:MM)',
                        ],
                        'notes' => [
                            'type' => 'string',
                            'description' => 'Zusätzliche Notizen',
                        ],
                    ],
                    'required' => ['customer_name', 'customer_phone', 'service_id', 'date', 'time'],
                ],
            ],
            [
                'name' => 'get_business_hours',
                'description' => 'Öffnungszeiten abrufen',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'date' => [
                            'type' => 'string',
                            'description' => 'Datum für Öffnungszeiten (optional)',
                        ],
                    ],
                ],
            ],
        ];
    }
    
    /**
     * Get boosted keywords for better recognition
     */
    private function getBookedKeywords(Branch $branch): array
    {
        $keywords = [
            // Standard German appointment keywords
            'Termin', 'Terminvereinbarung', 'Buchung', 'Reservierung',
            'morgen', 'übermorgen', 'nächste Woche',
            'vormittags', 'nachmittags', 'abends',
            
            // Company and branch names
            $branch->company->name,
            $branch->name,
            
            // Service names
            ...$branch->services->pluck('name')->toArray(),
            
            // Staff names
            ...$branch->staff->pluck('name')->toArray(),
        ];
        
        // Add custom keywords
        $customKeywords = $branch->getSetting('custom_keywords', []);
        if (!empty($customKeywords)) {
            $keywords = array_merge($keywords, $customKeywords);
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Validate branch has required information
     */
    private function validateBranch(Branch $branch): void
    {
        if (!$branch->company) {
            throw new \Exception('Branch must belong to a company');
        }
        
        if (!$branch->phone_number) {
            Log::warning('Branch has no phone number configured', [
                'branch_id' => $branch->id,
            ]);
        }
        
        if ($branch->services->isEmpty()) {
            throw new \Exception('Branch must have at least one service');
        }
    }
    
    /**
     * Get Retell API key for company
     */
    private function getRetellApiKey(Company $company): ?string
    {
        if ($company->retell_api_key) {
            // Try to decrypt if encrypted
            try {
                return decrypt($company->retell_api_key);
            } catch (\Exception $e) {
                // If decryption fails, assume it's already raw
                return $company->retell_api_key;
            }
        }
        
        return config('services.retell.api_key')
            ?? env('DEFAULT_RETELL_API_KEY');
    }
    
    /**
     * Store agent information in branch
     */
    private function storeAgentInfo(Branch $branch, array $agent): void
    {
        $branch->update([
            'retell_agent_id' => $agent['agent_id'],
            'retell_agent_status' => 'active',
            'retell_agent_created_at' => now(),
            'settings' => array_merge($branch->settings ?? [], [
                'retell_agent' => $agent,
            ]),
        ]);
    }
    
    /**
     * Assign phone number to agent
     */
    private function assignPhoneNumber(Branch $branch, string $agentId, string $apiKey): void
    {
        try {
            $retellService = new RetellV2Service($apiKey);
            
            // Update phone number configuration
            $result = $retellService->updatePhoneNumber($branch->phone_number, [
                'agent_id' => $agentId,
                'inbound_enabled' => true,
            ]);
            
            if ($result) {
                Log::info('Phone number assigned to agent', [
                    'branch_id' => $branch->id,
                    'agent_id' => $agentId,
                    'phone' => $branch->phone_number,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to assign phone number to agent', [
                'error' => $e->getMessage(),
                'branch_id' => $branch->id,
                'agent_id' => $agentId,
            ]);
        }
    }
    
    /**
     * Cache agent information
     */
    private function cacheAgentInfo(Branch $branch, array $agent): void
    {
        Cache::put(
            "retell_agent_{$branch->id}",
            $agent,
            now()->addDays(7)
        );
    }
    
    /**
     * Format business hours for prompt
     */
    private function formatBusinessHours(Branch $branch): string
    {
        $hours = $branch->business_hours ?? $branch->company->business_hours ?? [];
        
        if (empty($hours)) {
            return "Mo-Fr: 9:00-18:00, Sa: 10:00-14:00, So: Geschlossen";
        }
        
        $formatted = [];
        $dayMapping = [
            'monday' => 'Mo',
            'tuesday' => 'Di', 
            'wednesday' => 'Mi',
            'thursday' => 'Do',
            'friday' => 'Fr',
            'saturday' => 'Sa',
            'sunday' => 'So'
        ];
        
        foreach ($hours as $day => $times) {
            $dayName = $dayMapping[$day] ?? $day;
            
            if ((isset($times['closed']) && $times['closed']) || 
                (isset($times['isOpen']) && !$times['isOpen'])) {
                $formatted[] = "{$dayName}: Geschlossen";
            } else {
                $openTime = $times['openTime'] ?? $times['open'] ?? '09:00';
                $closeTime = $times['closeTime'] ?? $times['close'] ?? '18:00';
                $formatted[] = "{$dayName}: {$openTime}-{$closeTime}";
            }
        }
        
        return implode(", ", $formatted);
    }
    
    /**
     * Provision agents for all branches of a company
     */
    public function provisionAllBranches(Company $company): array
    {
        $results = [];
        
        foreach ($company->branches as $branch) {
            $results[$branch->id] = $this->createAgentForBranch($branch);
        }
        
        return $results;
    }
}