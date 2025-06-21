<?php

// Test phone number resolver directly
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\PhoneNumberResolver;
use App\Models\PhoneNumber;
use App\Models\Branch;

$resolver = new PhoneNumberResolver();

// Test webhook data
$webhookData = [
    'call_id' => '550e8400-e29b-41d4-a716-446655440000',
    'agent_id' => 'agent_9a8202a740cd3120d96fc27bb40b2c',
    'from_number' => '+491234567890',
    'to_number' => '+493083793369',
    'to' => '+493083793369',
    'direction' => 'inbound',
];

echo "=== Testing Phone Number Resolution ===\n\n";

// 1. Check phone number in database
echo "1. Checking phone number in database...\n";
$phoneRecord = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('number', '+493083793369')->first();
if ($phoneRecord) {
    echo "✅ Phone number found!\n";
    echo "  - Branch ID: " . $phoneRecord->branch_id . "\n";
    echo "  - Company ID: " . $phoneRecord->company_id . "\n";
    echo "  - Active: " . ($phoneRecord->active ? 'Yes' : 'No') . "\n";
    
    // Check branch
    $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($phoneRecord->branch_id);
    if ($branch) {
        echo "✅ Branch found!\n";
        echo "  - Name: " . $branch->name . "\n";
        echo "  - Company ID: " . $branch->company_id . "\n";
        echo "  - Active: " . ($branch->is_active ? 'Yes' : 'No') . "\n";
    } else {
        echo "❌ Branch not found!\n";
    }
} else {
    echo "❌ Phone number not found in database\n";
}

echo "\n2. Testing resolver...\n";
try {
    $result = $resolver->resolveFromWebhook($webhookData);
    echo "✅ Resolution successful!\n";
    echo "Result:\n";
    print_r($result);
} catch (\Exception $e) {
    echo "❌ Resolution failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}