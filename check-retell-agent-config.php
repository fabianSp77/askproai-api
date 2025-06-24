#!/usr/bin/env php
<?php

/**
 * Check Retell Agent Configuration
 * This script verifies that Retell agents are properly configured for appointment booking
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\RetellV2Service;
use Illuminate\Support\Facades\Log;

echo "\n=== RETELL AGENT CONFIGURATION CHECK ===\n\n";

// Get the first company
$company = Company::first();
if (!$company) {
    echo "❌ No company found in database!\n";
    exit(1);
}

echo "Company: " . $company->name . "\n";
echo "Retell Agent ID: " . ($company->retell_agent_id ?? 'Not configured') . "\n\n";

// Check if we have API key
$apiKey = $company->retell_api_key 
    ? decrypt($company->retell_api_key) 
    : config('services.retell.api_key');

if (!$apiKey) {
    echo "❌ No Retell API key configured!\n";
    exit(1);
}

try {
    $retellService = new RetellV2Service($apiKey);
    
    // List all agents
    echo "Fetching agents from Retell...\n";
    $agentsResponse = $retellService->listAgents();
    
    if (!isset($agentsResponse['agents'])) {
        echo "❌ Failed to fetch agents!\n";
        exit(1);
    }
    
    $agents = $agentsResponse['agents'];
    echo "Found " . count($agents) . " agents\n\n";
    
    // Check each agent
    foreach ($agents as $agent) {
        echo "Agent: " . $agent['agent_name'] . "\n";
        echo "ID: " . $agent['agent_id'] . "\n";
        
        // Check if this is the configured agent
        if ($agent['agent_id'] === $company->retell_agent_id) {
            echo "✅ This is the configured agent for the company\n";
        }
        
        // Check webhook URL
        $webhookUrl = $agent['webhook_url'] ?? null;
        if ($webhookUrl) {
            echo "Webhook URL: " . $webhookUrl . "\n";
            
            // Check if it's pointing to our system
            $expectedUrl = config('app.url') . '/api/retell/webhook';
            if ($webhookUrl === $expectedUrl) {
                echo "✅ Webhook URL is correct\n";
            } else {
                echo "⚠️  Webhook URL doesn't match expected: " . $expectedUrl . "\n";
            }
        } else {
            echo "❌ No webhook URL configured!\n";
        }
        
        // Check for custom functions (this is where appointment collection happens)
        echo "\nChecking agent prompt for appointment collection...\n";
        
        $prompt = $agent['general_prompt'] ?? '';
        $promptLower = strtolower($prompt);
        
        // Look for appointment-related keywords
        $appointmentKeywords = [
            'termin', 'appointment', 'buchen', 'book',
            'datum', 'date', 'uhrzeit', 'time',
            'collect_appointment_data', 'appointment_data'
        ];
        
        $foundKeywords = [];
        foreach ($appointmentKeywords as $keyword) {
            if (strpos($promptLower, $keyword) !== false) {
                $foundKeywords[] = $keyword;
            }
        }
        
        if (!empty($foundKeywords)) {
            echo "✅ Found appointment-related keywords in prompt: " . implode(', ', $foundKeywords) . "\n";
        } else {
            echo "⚠️  No appointment-related keywords found in prompt\n";
        }
        
        // Check for custom functions
        if (isset($agent['custom_functions']) && !empty($agent['custom_functions'])) {
            echo "\nCustom Functions:\n";
            foreach ($agent['custom_functions'] as $func) {
                echo "  - " . ($func['name'] ?? 'Unknown') . "\n";
                if (isset($func['name']) && $func['name'] === 'collect_appointment_data') {
                    echo "    ✅ Found collect_appointment_data function!\n";
                }
            }
        } else {
            echo "⚠️  No custom functions configured\n";
        }
        
        echo str_repeat('-', 60) . "\n\n";
    }
    
    // Recommendations
    echo "RECOMMENDATIONS:\n";
    echo str_repeat('=', 60) . "\n";
    
    $recommendations = [];
    
    if (empty($company->retell_agent_id)) {
        $recommendations[] = "Set the retell_agent_id in the companies table";
    }
    
    $hasCorrectWebhook = false;
    foreach ($agents as $agent) {
        if (($agent['webhook_url'] ?? '') === config('app.url') . '/api/retell/webhook') {
            $hasCorrectWebhook = true;
            break;
        }
    }
    
    if (!$hasCorrectWebhook) {
        $recommendations[] = "Configure webhook URL in Retell agent to: " . config('app.url') . '/api/retell/webhook';
    }
    
    $hasAppointmentFunction = false;
    foreach ($agents as $agent) {
        if (isset($agent['custom_functions'])) {
            foreach ($agent['custom_functions'] as $func) {
                if (($func['name'] ?? '') === 'collect_appointment_data') {
                    $hasAppointmentFunction = true;
                    break 2;
                }
            }
        }
    }
    
    if (!$hasAppointmentFunction) {
        $recommendations[] = "Add 'collect_appointment_data' custom function to Retell agent";
        echo "\nExample custom function configuration:\n";
        echo json_encode([
            'name' => 'collect_appointment_data',
            'description' => 'Sammelt Termindaten vom Anrufer',
            'parameters' => [
                'datum' => ['type' => 'string', 'description' => 'Gewünschtes Datum'],
                'uhrzeit' => ['type' => 'string', 'description' => 'Gewünschte Uhrzeit'],
                'name' => ['type' => 'string', 'description' => 'Name des Kunden'],
                'telefonnummer' => ['type' => 'string', 'description' => 'Telefonnummer'],
                'dienstleistung' => ['type' => 'string', 'description' => 'Gewünschte Dienstleistung'],
                'email' => ['type' => 'string', 'description' => 'E-Mail-Adresse (optional)']
            ]
        ], JSON_PRETTY_PRINT) . "\n";
    }
    
    if (empty($recommendations)) {
        echo "✅ Agent configuration looks good!\n";
    } else {
        echo "⚠️  Issues found:\n";
        foreach ($recommendations as $rec) {
            echo "  - $rec\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";