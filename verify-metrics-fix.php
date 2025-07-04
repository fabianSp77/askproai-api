<?php

/**
 * Verify Metrics Fix
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Filament\Admin\Pages\RetellUltimateControlCenter;
use App\Models\User;

echo "\n=== Verify Metrics Fix ===\n\n";

// Login as admin
$user = User::where('email', 'admin@askproai.com')->first();
if ($user) {
    \Illuminate\Support\Facades\Auth::login($user);
}

// Create page instance
$page = new RetellUltimateControlCenter();

// Test getAgentMetrics directly
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';
$page->companyId = 1; // Set company ID

echo "1. Teste getAgentMetrics() direkt:\n";
$metrics = $page->getAgentMetrics($agentId);

echo "   Metrics für Agent $agentId:\n";
echo "   - Calls Today: " . $metrics['calls_today'] . "\n";
echo "   - Success Rate: " . $metrics['success_rate'] . "%\n";
echo "   - Avg Duration: " . $metrics['avg_duration'] . "\n";
echo "   - Status: " . $metrics['status'] . "\n\n";

// Now test full loadAgents
echo "2. Teste loadAgents():\n";
$page->loadAgents();

$agentCount = count($page->agents);
echo "   Anzahl geladener Agents: $agentCount\n";

if ($agentCount > 0) {
    // Find our active agent
    $activeAgent = null;
    foreach ($page->agents as $agent) {
        if ($agent['agent_id'] === $agentId) {
            $activeAgent = $agent;
            break;
        }
    }
    
    if ($activeAgent) {
        echo "\n3. Aktiver Agent Details:\n";
        echo "   - Name: " . $activeAgent['display_name'] . "\n";
        echo "   - Active: " . ($activeAgent['is_active'] ? 'JA' : 'NEIN') . "\n";
        
        if (isset($activeAgent['metrics'])) {
            echo "   - Metrics vorhanden: JA\n";
            echo "     * Calls Today: " . $activeAgent['metrics']['calls_today'] . "\n";
            echo "     * Success Rate: " . $activeAgent['metrics']['success_rate'] . "%\n";
            echo "     * Avg Duration: " . $activeAgent['metrics']['avg_duration'] . "\n";
            echo "     * Status: " . $activeAgent['metrics']['status'] . "\n";
        } else {
            echo "   - Metrics vorhanden: NEIN ❌\n";
        }
    }
}

echo "\n✅ Test abgeschlossen\n";
echo "Bitte prüfe jetzt die UI: https://api.askproai.de/admin/retell-ultimate-control-center\n";