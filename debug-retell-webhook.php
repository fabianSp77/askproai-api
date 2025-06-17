<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Branch;
use App\Models\Company;

// Find the branch with the agent
$branch = Branch::where('retell_agent_id', 'agent_9a8202a740cd3120d96fcfda1e')->first();

if (!$branch) {
    // Try all branches
    echo "Looking for branch with agent...\n";
    $branches = Branch::all();
    foreach ($branches as $b) {
        echo "- {$b->name} | Agent: " . ($b->retell_agent_id ?: 'NONE') . "\n";
    }
    exit;
}

if (!$branch) {
    echo "Branch not found!\n";
    exit;
}

echo "Branch: {$branch->name}\n";
echo "Agent ID: {$branch->retell_agent_id}\n";
echo "Company: {$branch->company->name}\n";
echo "Cal.com API Key: " . ($branch->company->calcom_api_key ? 'SET' : 'NOT SET') . "\n\n";

// Check webhook URLs
echo "=== WEBHOOK URLS ===\n";
echo "Unified Webhook: " . route('webhook.unified') . "\n";
echo "Retell Webhook: " . url('/api/retell/webhook') . "\n";
echo "Function Call: " . url('/api/retell/function-call') . "\n\n";

// Check if company has event types
$eventTypes = \App\Models\CalcomEventType::where('company_id', $branch->company_id)->get();
echo "=== CAL.COM EVENT TYPES ===\n";
if ($eventTypes->isEmpty()) {
    echo "NO EVENT TYPES FOUND! This will cause booking to fail.\n";
} else {
    foreach ($eventTypes as $type) {
        echo "- {$type->name} (ID: {$type->calcom_id})\n";
    }
}

// Check recent calls
echo "\n=== RECENT CALLS (Last 5) ===\n";
$calls = \App\Models\Call::where('company_id', $branch->company_id)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();
    
if ($calls->isEmpty()) {
    echo "No calls found.\n";
} else {
    foreach ($calls as $call) {
        echo "- {$call->created_at} | Status: {$call->status} | Duration: {$call->duration}s\n";
    }
}

// Check recent webhook logs
echo "\n=== CHECKING WEBHOOK LOGS ===\n";
$logFile = storage_path('logs/laravel.log');
$logs = shell_exec("tail -100 $logFile | grep -i 'retell\\|webhook\\|function.*call' | tail -10");
if ($logs) {
    echo $logs;
} else {
    echo "No recent webhook activity in logs.\n";
}