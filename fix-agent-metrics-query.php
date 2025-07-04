<?php

/**
 * Fix Agent Metrics Query
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use Carbon\Carbon;

echo "\n=== Fix Agent Metrics Query ===\n\n";

// Test the problematic query
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';
$companyId = 1;

echo "1. Teste Original Query (mit created_at):\n";
$todayStart = now()->startOfDay();
$todayEnd = now()->endOfDay();

// Original query
$callsCreatedAt = Call::where('company_id', $companyId)
    ->where('retell_agent_id', $agentId)
    ->whereBetween('created_at', [$todayStart, $todayEnd])
    ->count();
    
echo "   Calls mit created_at heute: $callsCreatedAt\n";

// Correct query
echo "\n2. Teste korrekte Query (mit start_timestamp):\n";
$callsStartTimestamp = Call::where('company_id', $companyId)
    ->where('retell_agent_id', $agentId)
    ->whereBetween('start_timestamp', [$todayStart, $todayEnd])
    ->count();
    
echo "   Calls mit start_timestamp heute: $callsStartTimestamp\n";

// Without tenant scope
echo "\n3. Teste ohne TenantScope:\n";
$callsNoScope = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', $companyId)
    ->where('retell_agent_id', $agentId)
    ->whereBetween('start_timestamp', [$todayStart, $todayEnd])
    ->count();
    
echo "   Calls ohne TenantScope: $callsNoScope\n";

// Check field names
echo "\n4. Prüfe Feldnamen:\n";
$sampleCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('retell_agent_id', $agentId)
    ->first();
    
if ($sampleCall) {
    echo "   - duration_seconds: " . ($sampleCall->duration_seconds ?? 'NULL') . "\n";
    echo "   - call_length: " . ($sampleCall->call_length ?? 'NULL') . "\n";
    echo "   - start_timestamp: " . ($sampleCall->start_timestamp ?? 'NULL') . "\n";
    echo "   - created_at: " . ($sampleCall->created_at ?? 'NULL') . "\n";
}

// The fix needed in RetellUltimateControlCenter.php
echo "\n5. Benötigte Änderungen in getAgentMetrics():\n";
echo "   - Verwende 'start_timestamp' statt 'created_at'\n";
echo "   - Verwende 'duration_seconds' statt 'call_length'\n";
echo "   - Füge withoutGlobalScope() hinzu\n";

echo "\n=== Analyse abgeschlossen ===\n";