<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\Branch;
use App\Services\PhoneNumberResolver;

echo "\n=== TESTING PHONE TO BRANCH RESOLUTION ===\n\n";

// 1. Show all branches with phone numbers
echo "=== BRANCHES WITH PHONE NUMBERS ===\n";
$branches = Branch::whereNotNull('phone_number')->get();
foreach ($branches as $branch) {
    echo "Branch: {$branch->name} (ID: {$branch->id})\n";
    echo "  Company ID: {$branch->company_id}\n";
    echo "  Phone: {$branch->phone_number}\n";
    echo "  Retell Agent: " . ($branch->retell_agent_id ?? 'NONE') . "\n\n";
}

// 2. Test the resolver with sample webhook data
echo "=== TESTING PHONE NUMBER RESOLVER ===\n";
$resolver = new PhoneNumberResolver();

// Test cases
$testCases = [
    [
        'name' => 'Berlin Branch Call',
        'webhook' => [
            'call_id' => 'test_001',
            'from' => '+49 170 1234567',
            'to' => '+493083793369',  // Berlin number
            'phone_number' => '+49 170 1234567'
        ]
    ],
    [
        'name' => 'Unknown Number Call',
        'webhook' => [
            'call_id' => 'test_002',
            'from' => '+49 170 9999999',
            'to' => '+49 30 9999999',  // Unknown number
            'phone_number' => '+49 170 9999999'
        ]
    ],
    [
        'name' => 'With Metadata',
        'webhook' => [
            'call_id' => 'test_003',
            'from' => '+49 170 5555555',
            'metadata' => [
                'askproai_branch_id' => $branches->first()?->id
            ]
        ]
    ]
];

foreach ($testCases as $test) {
    echo "\nTest: {$test['name']}\n";
    echo "Webhook data: " . json_encode($test['webhook'], JSON_PRETTY_PRINT) . "\n";
    
    $result = $resolver->resolveFromWebhook($test['webhook']);
    
    echo "Resolution result:\n";
    echo "  Branch ID: " . ($result['branch_id'] ?? 'NULL') . "\n";
    echo "  Company ID: " . ($result['company_id'] ?? 'NULL') . "\n";
    echo "  Agent ID: " . ($result['agent_id'] ?? 'NULL') . "\n";
    
    if ($result['branch_id']) {
        $branch = Branch::find($result['branch_id']);
        echo "  → Resolved to: {$branch->name}\n";
    } else {
        echo "  → Could not resolve to any branch\n";
    }
}

// 3. Analyze existing calls
echo "\n\n=== ANALYZING EXISTING CALLS ===\n";
$recentCalls = Call::latest()->limit(10)->get();

$withBranch = 0;
$withoutBranch = 0;
$withToNumber = 0;

foreach ($recentCalls as $call) {
    if ($call->branch_id) {
        $withBranch++;
    } else {
        $withoutBranch++;
    }
    
    if ($call->to_number) {
        $withToNumber++;
    }
}

echo "Recent calls analysis:\n";
echo "  With branch assignment: {$withBranch}\n";
echo "  Without branch assignment: {$withoutBranch}\n";
echo "  With to_number stored: {$withToNumber}\n";

// 4. Test updating old calls
echo "\n=== TESTING RETROACTIVE BRANCH ASSIGNMENT ===\n";
$unassignedCalls = Call::whereNull('branch_id')
    ->whereNotNull('to_number')
    ->limit(5)
    ->get();

if ($unassignedCalls->isEmpty()) {
    echo "No unassigned calls with to_number found.\n";
} else {
    foreach ($unassignedCalls as $call) {
        echo "\nCall ID: {$call->id}\n";
        echo "  To: {$call->to_number}\n";
        
        // Try to resolve
        $webhookData = [
            'to' => $call->to_number,
            'from' => $call->from_number ?? $call->phone_number
        ];
        
        $result = $resolver->resolveFromWebhook($webhookData);
        
        if ($result['branch_id']) {
            $branch = Branch::find($result['branch_id']);
            echo "  → Can be assigned to: {$branch->name}\n";
            
            // Update the call
            $call->update([
                'branch_id' => $result['branch_id'],
                'company_id' => $result['company_id']
            ]);
            echo "  ✓ Updated!\n";
        } else {
            echo "  → No matching branch found\n";
        }
    }
}

echo "\n=== TEST COMPLETE ===\n";