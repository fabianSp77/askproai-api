<?php

/**
 * Hair Salon MCP Agent Configuration Script
 * 
 * This script configures the Retell.ai agent with ID agent_d7da9e5c49c4ccfff2526df5c1
 * for the Hair Salon MCP integration.
 * 
 * Usage: php configure-hair-salon-mcp-agent.php
 * 
 * Requirements:
 * - RETELL_API_KEY environment variable must be set
 * - Agent agent_d7da9e5c49c4ccfff2526df5c1 must exist in Retell.ai
 * 
 * Author: Claude Code
 * Date: 2025-08-07
 */

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\RetellV2Service;
use Illuminate\Support\Facades\Log;

// Constants
const AGENT_ID = 'agent_d7da9e5c49c4ccfff2526df5c1';
const MCP_ENDPOINT = 'https://api.askproai.de/api/v2/hair-salon-mcp';

/**
 * Display script header
 */
function showHeader(): void {
    echo str_repeat("=", 80) . "\n";
    echo "Hair Salon MCP Agent Configuration Script\n";
    echo "Agent ID: " . AGENT_ID . "\n";
    echo "MCP Endpoint: " . MCP_ENDPOINT . "\n";
    echo str_repeat("=", 80) . "\n\n";
}

/**
 * Validate environment and requirements
 */
function validateEnvironment(): string {
    $apiKey = env('RETELL_API_KEY');
    
    if (!$apiKey) {
        echo "❌ ERROR: RETELL_API_KEY environment variable is not set.\n";
        echo "   Please set it in your .env file or environment.\n\n";
        exit(1);
    }
    
    echo "✅ Environment validation passed\n";
    echo "   API Key: " . substr($apiKey, 0, 8) . "...\n\n";
    
    return $apiKey;
}

/**
 * Create Hair Salon MCP custom functions
 */
function createHairSalonFunctions(): array {
    return [
        [
            "name" => "list_services",
            "description" => "Alle verfügbaren Friseur-Dienstleistungen und Preise anzeigen",
            "url" => MCP_ENDPOINT . "/list-services",
            "speak_during_execution" => false,
            "speak_after_execution" => true,
            "properties" => [
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "category" => [
                            "type" => "string",
                            "description" => "Kategorie der Dienstleistungen (optional: 'herren', 'damen', 'kinder')",
                            "enum" => ["herren", "damen", "kinder", "alle"]
                        ]
                    ]
                ]
            ]
        ],
        [
            "name" => "check_availability",
            "description" => "Verfügbare Termine für ein bestimmtes Datum und Service prüfen",
            "url" => MCP_ENDPOINT . "/check-availability",
            "speak_during_execution" => true,
            "speak_after_execution" => true,
            "properties" => [
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "date" => [
                            "type" => "string",
                            "description" => "Gewünschtes Datum (z.B. '2025-08-07', 'heute', 'morgen')"
                        ],
                        "service" => [
                            "type" => "string",
                            "description" => "Gewünschte Dienstleistung (z.B. 'Herrenschnitt', 'Damenschnitt', 'Färben')"
                        ],
                        "preferred_time" => [
                            "type" => "string",
                            "description" => "Bevorzugte Uhrzeit (optional, z.B. '10:00', '14:30')"
                        ]
                    ],
                    "required" => ["date", "service"]
                ]
            ]
        ],
        [
            "name" => "book_appointment",
            "description" => "Termin für Friseur-Dienstleistung buchen mit Kundendaten",
            "url" => MCP_ENDPOINT . "/book-appointment",
            "speak_during_execution" => true,
            "speak_after_execution" => true,
            "properties" => [
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "call_id" => [
                            "type" => "string",
                            "description" => "Call ID für Referenz (immer {{call_id}} verwenden)"
                        ],
                        "customer_name" => [
                            "type" => "string",
                            "description" => "Vollständiger Name des Kunden"
                        ],
                        "phone_number" => [
                            "type" => "string",
                            "description" => "Telefonnummer des Kunden ({{caller_phone_number}} verwenden)"
                        ],
                        "email" => [
                            "type" => "string",
                            "description" => "E-Mail-Adresse für Terminbestätigung (optional)"
                        ],
                        "service" => [
                            "type" => "string",
                            "description" => "Gewünschte Dienstleistung"
                        ],
                        "date" => [
                            "type" => "string",
                            "description" => "Termindatum (ISO Format: YYYY-MM-DD)"
                        ],
                        "time" => [
                            "type" => "string",
                            "description" => "Terminzeit (24h Format: HH:MM)"
                        ],
                        "notes" => [
                            "type" => "string",
                            "description" => "Zusätzliche Notizen oder Wünsche (optional)"
                        ]
                    ],
                    "required" => ["call_id", "customer_name", "phone_number", "service", "date", "time"]
                ]
            ]
        ],
        [
            "name" => "schedule_callback",
            "description" => "Rückruf zu einem bestimmten Zeitpunkt vereinbaren",
            "url" => MCP_ENDPOINT . "/schedule-callback",
            "speak_during_execution" => true,
            "speak_after_execution" => true,
            "properties" => [
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "call_id" => [
                            "type" => "string",
                            "description" => "Call ID für Referenz (immer {{call_id}} verwenden)"
                        ],
                        "phone_number" => [
                            "type" => "string",
                            "description" => "Telefonnummer für Rückruf ({{caller_phone_number}} verwenden)"
                        ],
                        "customer_name" => [
                            "type" => "string",
                            "description" => "Name des Kunden"
                        ],
                        "callback_date" => [
                            "type" => "string",
                            "description" => "Datum für Rückruf (z.B. 'heute', 'morgen', '2025-08-08')"
                        ],
                        "callback_time" => [
                            "type" => "string",
                            "description" => "Uhrzeit für Rückruf (z.B. '14:00', '09:30')"
                        ],
                        "reason" => [
                            "type" => "string",
                            "description" => "Grund für den Rückruf (z.B. 'Terminbestätigung', 'Beratung')"
                        ]
                    ],
                    "required" => ["call_id", "phone_number", "customer_name", "callback_date", "callback_time", "reason"]
                ]
            ]
        ]
    ];
}

/**
 * Test MCP endpoint connectivity
 */
function testMCPEndpoint(): bool {
    echo "🔍 Testing MCP endpoint connectivity...\n";
    
    try {
        $response = file_get_contents(MCP_ENDPOINT . "/health", false, stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'ignore_errors' => true
            ]
        ]));
        
        if ($response !== false) {
            echo "✅ MCP endpoint is reachable\n\n";
            return true;
        } else {
            echo "⚠️  MCP endpoint might not be available, but continuing...\n\n";
            return false;
        }
    } catch (Exception $e) {
        echo "⚠️  Could not test MCP endpoint: " . $e->getMessage() . "\n";
        echo "   Continuing with configuration anyway...\n\n";
        return false;
    }
}

/**
 * Fetch current agent configuration
 */
function fetchCurrentAgent(RetellV2Service $retellService): ?array {
    echo "📡 Fetching current agent configuration...\n";
    
    try {
        $agent = $retellService->getAgent(AGENT_ID);
        
        if (!$agent) {
            echo "❌ ERROR: Could not fetch agent with ID: " . AGENT_ID . "\n";
            echo "   Please verify the agent exists in your Retell.ai dashboard.\n\n";
            return null;
        }
        
        echo "✅ Agent found: " . ($agent['agent_name'] ?? 'Unnamed Agent') . "\n";
        echo "   LLM ID: " . ($agent['llm_id'] ?? 'N/A') . "\n";
        echo "   Voice ID: " . ($agent['voice_id'] ?? 'N/A') . "\n\n";
        
        return $agent;
        
    } catch (Exception $e) {
        echo "❌ ERROR fetching agent: " . $e->getMessage() . "\n\n";
        return null;
    }
}

/**
 * Update agent with MCP functions
 */
function updateAgentWithMCPFunctions(RetellV2Service $retellService, array $agent, array $mcpFunctions): bool {
    echo "🔧 Updating agent with Hair Salon MCP functions...\n";
    
    try {
        // Prepare update configuration
        $updateConfig = $agent;
        
        // Keep existing prompt unchanged as requested
        $currentPrompt = $agent['llm_configuration']['general_prompt'] ?? '';
        echo "   Keeping existing prompt unchanged\n";
        
        // Add MCP functions to existing functions
        $existingFunctions = $agent['llm_configuration']['general_tools'] ?? [];
        
        // Remove any existing Hair Salon MCP functions to avoid duplicates
        $cleanedFunctions = array_filter($existingFunctions, function($func) {
            $mcpFunctionNames = ['list_services', 'check_availability', 'book_appointment', 'schedule_callback'];
            return !in_array($func['name'] ?? '', $mcpFunctionNames);
        });
        
        // Add MCP functions
        $allFunctions = array_merge(array_values($cleanedFunctions), $mcpFunctions);
        
        $updateConfig['llm_configuration']['general_tools'] = $allFunctions;
        
        // Ensure user_dtmf_options is handled correctly
        if (isset($updateConfig['user_dtmf_options']) && is_array($updateConfig['user_dtmf_options'])) {
            if (empty($updateConfig['user_dtmf_options'])) {
                $updateConfig['user_dtmf_options'] = new \stdClass();
            }
        } elseif (!isset($updateConfig['user_dtmf_options'])) {
            $updateConfig['user_dtmf_options'] = new \stdClass();
        }
        
        // Remove fields that shouldn't be in the update payload
        unset($updateConfig['agent_id']);
        unset($updateConfig['created_at']);
        unset($updateConfig['last_modification']);
        
        // Perform the update
        $result = $retellService->updateAgent(AGENT_ID, $updateConfig);
        
        if ($result) {
            echo "✅ Agent updated successfully!\n";
            
            // Display summary
            echo "\n" . str_repeat("-", 60) . "\n";
            echo "CONFIGURATION SUMMARY\n";
            echo str_repeat("-", 60) . "\n";
            echo "Agent Name: " . ($result['agent_name'] ?? 'Unknown') . "\n";
            echo "Agent ID: " . AGENT_ID . "\n";
            echo "Total Functions: " . count($allFunctions) . "\n";
            echo "Hair Salon MCP Functions Added: " . count($mcpFunctions) . "\n";
            echo "MCP Endpoint: " . MCP_ENDPOINT . "\n";
            echo "Prompt: Unchanged (as requested)\n";
            
            echo "\nHair Salon MCP Functions:\n";
            foreach ($mcpFunctions as $func) {
                echo "  • " . $func['name'] . " - " . $func['description'] . "\n";
            }
            
            echo "\n✅ Configuration completed successfully!\n";
            echo "   The agent is now ready to handle Hair Salon appointments via MCP.\n\n";
            
            return true;
        } else {
            echo "❌ ERROR: Failed to update agent configuration\n\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "❌ ERROR during agent update: " . $e->getMessage() . "\n\n";
        Log::error('Hair Salon MCP Agent Configuration Error', [
            'agent_id' => AGENT_ID,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return false;
    }
}

/**
 * Verify configuration after update
 */
function verifyConfiguration(RetellV2Service $retellService): bool {
    echo "🔍 Verifying configuration...\n";
    
    try {
        $agent = $retellService->getAgent(AGENT_ID);
        
        if (!$agent) {
            echo "❌ Could not fetch agent for verification\n\n";
            return false;
        }
        
        $functions = $agent['llm_configuration']['general_tools'] ?? [];
        $mcpFunctionNames = ['list_services', 'check_availability', 'book_appointment', 'schedule_callback'];
        
        $foundMcpFunctions = [];
        foreach ($functions as $func) {
            if (in_array($func['name'] ?? '', $mcpFunctionNames)) {
                $foundMcpFunctions[] = $func['name'];
            }
        }
        
        echo "   Found MCP functions: " . implode(', ', $foundMcpFunctions) . "\n";
        
        if (count($foundMcpFunctions) === 4) {
            echo "✅ All Hair Salon MCP functions are properly configured\n\n";
            return true;
        } else {
            echo "⚠️  Some MCP functions may be missing\n\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "❌ ERROR during verification: " . $e->getMessage() . "\n\n";
        return false;
    }
}

/**
 * Main execution function
 */
function main(): void {
    try {
        showHeader();
        
        // Step 1: Validate environment
        $apiKey = validateEnvironment();
        
        // Step 2: Initialize Retell service
        $retellService = new RetellV2Service($apiKey);
        
        // Step 3: Test MCP endpoint (optional, non-blocking)
        testMCPEndpoint();
        
        // Step 4: Fetch current agent
        $agent = fetchCurrentAgent($retellService);
        if (!$agent) {
            exit(1);
        }
        
        // Step 5: Create MCP functions
        $mcpFunctions = createHairSalonFunctions();
        
        // Step 6: Update agent with MCP functions
        $success = updateAgentWithMCPFunctions($retellService, $agent, $mcpFunctions);
        if (!$success) {
            exit(1);
        }
        
        // Step 7: Verify configuration
        verifyConfiguration($retellService);
        
        echo str_repeat("=", 80) . "\n";
        echo "🎉 Hair Salon MCP Agent Configuration Completed Successfully!\n";
        echo str_repeat("=", 80) . "\n";
        
    } catch (Exception $e) {
        echo "💥 FATAL ERROR: " . $e->getMessage() . "\n";
        echo "   Stack trace: " . $e->getTraceAsString() . "\n\n";
        
        Log::error('Hair Salon MCP Configuration Fatal Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        exit(1);
    }
}

// Execute main function
main();