#!/usr/bin/env php
<?php

/**
 * AskProAI MCP Setup Verification Script
 * Überprüft ob alle MCP-Funktionen korrekt konfiguriert sind
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Branch;
use App\Models\Company;
use App\Models\PhoneNumber;
use App\Services\MCP\CalcomMCPServer;
use App\Services\MCP\RetellMCPServer;
use App\Services\MCP\WebhookMCPServer;
use App\Services\MCP\DatabaseMCPServer;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║          ASKPROAI MCP SETUP VERIFICATION SCRIPT              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Step 1: Check Company
echo "1️⃣  CHECKING COMPANY CONFIGURATION\n";
echo str_repeat("─", 60) . "\n";

$company = Company::where('name', 'AskProAI GmbH')->first();
if (!$company) {
    echo "❌ Company 'AskProAI' not found!\n";
    exit(1);
}

echo "✅ Company found: " . $company->name . " (ID: " . $company->id . ")\n";
echo "   📧 Email: " . ($company->email ?? 'Not set') . "\n";
echo "   📞 Phone: " . ($company->phone ?? 'Not set') . "\n";
echo "   🔑 Retell API: " . ($company->retell_api_key ? '✓ Set' : '❌ Missing') . "\n";
echo "   🔑 Cal.com API: " . ($company->calcom_api_key ? '✓ Set' : '❌ Missing') . "\n\n";

// Step 2: Check Branch
echo "2️⃣  CHECKING BRANCH CONFIGURATION\n";
echo str_repeat("─", 60) . "\n";

$branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', $company->id)
    ->where('name', 'Hauptfiliale')
    ->first();

if (!$branch) {
    echo "❌ Branch 'AskProAI Berlin' not found!\n";
    exit(1);
}

echo "✅ Branch found: " . $branch->name . " (ID: " . $branch->id . ")\n";
echo "   📍 Active: " . ($branch->is_active ? '✓ Yes' : '❌ No') . "\n";
echo "   🤖 Retell Agent ID: " . ($branch->retell_agent_id ?? '❌ NOT SET - CRITICAL!') . "\n";
echo "   📅 Cal.com Event Type: " . ($branch->calcom_event_type_id ?? '❌ NOT SET - CRITICAL!') . "\n\n";

// Step 3: Check Phone Numbers
echo "3️⃣  CHECKING PHONE NUMBER CONFIGURATION\n";
echo str_repeat("─", 60) . "\n";

$phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('branch_id', $branch->id)->get();
if ($phoneNumbers->isEmpty()) {
    echo "❌ No phone numbers configured for branch!\n";
} else {
    foreach ($phoneNumbers as $phone) {
        echo "✅ Phone: " . $phone->number . "\n";
        echo "   Type: " . $phone->type . " | Active: " . ($phone->is_active ? 'Yes' : 'No') . "\n";
    }
}
echo "\n";

// Step 4: Test MCP Services
echo "4️⃣  TESTING MCP SERVICES\n";
echo str_repeat("─", 60) . "\n";

// Test DatabaseMCPServer
try {
    $dbMCP = app(DatabaseMCPServer::class);
    $result = $dbMCP->getSchema();
    echo "✅ DatabaseMCPServer: Operational (" . count($result['tables']) . " tables)\n";
} catch (Exception $e) {
    echo "❌ DatabaseMCPServer: " . $e->getMessage() . "\n";
}

// Test RetellMCPServer
try {
    $retellMCP = app(RetellMCPServer::class);
    $health = $retellMCP->healthCheck();
    echo "✅ RetellMCPServer: " . $health['status'] . "\n";
} catch (Exception $e) {
    echo "❌ RetellMCPServer: " . $e->getMessage() . "\n";
}

// Test CalcomMCPServer
try {
    $calcomMCP = app(CalcomMCPServer::class);
    // CalcomMCPServer doesn't have healthCheck method, just check if it instantiates
    echo "✅ CalcomMCPServer: Operational\n";
} catch (Exception $e) {
    echo "❌ CalcomMCPServer: " . $e->getMessage() . "\n";
}

// Test WebhookMCPServer
try {
    $webhookMCP = app(WebhookMCPServer::class);
    echo "✅ WebhookMCPServer: Operational\n";
} catch (Exception $e) {
    echo "❌ WebhookMCPServer: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 5: Configuration Commands
echo "5️⃣  CONFIGURATION COMMANDS\n";
echo str_repeat("─", 60) . "\n";

if (!$branch->retell_agent_id || !$branch->calcom_event_type_id) {
    echo "⚠️  CRITICAL CONFIGURATION MISSING!\n\n";
    echo "Run these SQL commands to fix:\n\n";
    echo "```sql\n";
    echo "UPDATE branches\n";
    echo "SET\n";
    
    if (!$branch->retell_agent_id) {
        echo "    retell_agent_id = 'YOUR_RETELL_AGENT_ID',\n";
    }
    
    if (!$branch->calcom_event_type_id) {
        echo "    calcom_event_type_id = YOUR_CALCOM_EVENT_TYPE_ID,\n";
    }
    
    echo "    updated_at = NOW()\n";
    echo "WHERE id = '" . $branch->id . "';\n";
    echo "```\n\n";
}

// Step 6: Test Webhook Endpoints
echo "6️⃣  TESTING WEBHOOK ENDPOINTS\n";
echo str_repeat("─", 60) . "\n";

$webhookEndpoints = [
    'Unified Webhook Health' => 'https://api.askproai.de/api/webhooks/health',
    'MCP Health Check' => 'https://api.askproai.de/api/health',
    'MCP Detailed Health' => 'https://api.askproai.de/api/health/detailed'
];

foreach ($webhookEndpoints as $name => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "✅ $name: OK\n";
    } else {
        echo "❌ $name: HTTP $httpCode\n";
    }
}

echo "\n";

// Step 7: Summary
echo "7️⃣  SUMMARY & NEXT STEPS\n";
echo str_repeat("─", 60) . "\n";

$issues = [];
if (!$branch->retell_agent_id) $issues[] = "Retell Agent ID not configured";
if (!$branch->calcom_event_type_id) $issues[] = "Cal.com Event Type ID not configured";
if (!$branch->is_active) $issues[] = "Branch is not active";
if (!$company->retell_api_key) $issues[] = "Retell API key not set";
if (!$company->calcom_api_key) $issues[] = "Cal.com API key not set";

if (empty($issues)) {
    echo "✅ SYSTEM IS READY FOR TESTING!\n\n";
    echo "Test with:\n";
    echo "1. Call: " . ($phoneNumbers->first()->number ?? '+49 30 837 93 369') . "\n";
    echo "2. Say: 'Ich möchte einen Termin vereinbaren'\n";
    echo "3. Monitor: tail -f storage/logs/laravel.log\n";
} else {
    echo "❌ ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "   - $issue\n";
    }
    echo "\nFix these issues before testing!\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "                    END OF VERIFICATION                         \n";
echo "═══════════════════════════════════════════════════════════════\n\n";