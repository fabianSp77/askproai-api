<?php
/**
 * Verify Branch and Agent Configuration
 * Branch: Friseur 1 zentrale (34c4d48e-4753-4715-9c30-c55843a943e8)
 * Agent: agent_45daa54928c5768b52ba3db736
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Branch;
use Illuminate\Support\Facades\DB;

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_45daa54928c5768b52ba3db736';
$branchId = '34c4d48e-4753-4715-9c30-c55843a943e8';

echo "=== BRANCH AND AGENT VERIFICATION ===\n\n";

// ============================================
// 1. VERIFY AGENT V99 IS PUBLISHED
// ============================================
echo "1. Checking Agent V99 publish status...\n";

$ch = curl_init("https://api.retellai.com/list-agents");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$agents = json_decode($response, true);
curl_close($ch);

$foundV99 = false;
$foundPublished = null;

foreach ($agents as $agent) {
    if ($agent['agent_id'] === $agentId) {
        echo "   Agent: {$agent['agent_name']}\n";
        echo "   Version: {$agent['version']}\n";
        echo "   Published: " . ($agent['is_published'] ? '✅ YES' : '❌ NO') . "\n";

        if ($agent['version'] === 99) {
            $foundV99 = true;
            if ($agent['is_published']) {
                echo "   ✅ V99 IS PUBLISHED!\n";
                $foundPublished = true;
            } else {
                echo "   ❌ V99 NOT PUBLISHED!\n";
                $foundPublished = false;
            }
        }
        echo "\n";
    }
}

if (!$foundV99) {
    echo "   ⚠️  V99 not found in agent list\n\n";
}

// ============================================
// 2. CHECK BRANCH CONFIGURATION
// ============================================
echo "2. Checking Branch configuration...\n";

$branch = Branch::find($branchId);

if (!$branch) {
    die("❌ Branch not found: {$branchId}\n");
}

echo "   ✅ Branch found: {$branch->name}\n";
echo "   Company ID: {$branch->company_id}\n";
echo "   Active: " . ($branch->is_active ? '✅ YES' : '❌ NO') . "\n";
echo "   Address: {$branch->address}\n";
echo "   City: {$branch->city}\n";
echo "   Phone: {$branch->phone}\n";
echo "\n";

// ============================================
// 3. CHECK RETELL CONFIGURATION
// ============================================
echo "3. Checking Retell configuration...\n";

// Check if branch has retell_agent_id
$retellAgentId = $branch->retell_agent_id ?? null;
$retellPhoneNumber = $branch->retell_phone_number ?? null;

echo "   Retell Agent ID: " . ($retellAgentId ?? 'NOT SET') . "\n";
echo "   Retell Phone: " . ($retellPhoneNumber ?? 'NOT SET') . "\n";

if ($retellAgentId) {
    if ($retellAgentId === $agentId) {
        echo "   ✅ Correct agent assigned!\n";
    } else {
        echo "   ⚠️  Different agent assigned: {$retellAgentId}\n";
        echo "   Expected: {$agentId}\n";
    }
} else {
    echo "   ⚠️  No agent assigned to branch\n";
}

echo "\n";

// ============================================
// 4. CHECK PHONE NUMBER ASSIGNMENT
// ============================================
echo "4. Checking phone number assignment in Retell...\n";

$ch = curl_init("https://api.retellai.com/list-phone-numbers");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$phoneNumbers = json_decode($response, true);
curl_close($ch);

$foundPhone = false;
$expectedPhone = '+4916043662180'; // Test number

foreach ($phoneNumbers as $phone) {
    if ($phone['phone_number'] === $expectedPhone) {
        $foundPhone = true;
        echo "   Phone: {$phone['phone_number']}\n";
        echo "   Agent ID: {$phone['agent_id']}\n";
        echo "   Nickname: " . ($phone['nickname'] ?? 'None') . "\n";

        if ($phone['agent_id'] === $agentId) {
            echo "   ✅ Correct agent assigned to phone!\n";
        } else {
            echo "   ⚠️  Wrong agent assigned!\n";
            echo "   Expected: {$agentId}\n";
            echo "   Actual: {$phone['agent_id']}\n";
        }
    }
}

if (!$foundPhone) {
    echo "   ⚠️  Test phone number {$expectedPhone} not found\n";
    echo "   Showing all phone numbers:\n";
    foreach ($phoneNumbers as $phone) {
        $agentIdDisplay = $phone['agent_id'] ?? 'NO AGENT';
        echo "   - {$phone['phone_number']} → {$agentIdDisplay}\n";
    }
}

echo "\n";

// ============================================
// 5. CHECK SERVICES CONFIGURATION
// ============================================
echo "5. Checking services for branch...\n";

$services = DB::table('services')
    ->where('branch_id', $branchId)
    ->where('is_active', true)
    ->select('id', 'name', 'duration', 'price', 'is_active')
    ->get();

echo "   Active services: " . $services->count() . "\n";

if ($services->count() > 0) {
    echo "   Services:\n";
    foreach ($services as $service) {
        echo "   - {$service->name} ({$service->duration}min, €{$service->price})\n";
    }
} else {
    echo "   ⚠️  No active services found!\n";
}

echo "\n";

// ============================================
// 6. CHECK STAFF CONFIGURATION
// ============================================
echo "6. Checking staff for branch...\n";

$staff = DB::table('users')
    ->join('branch_staff', 'users.id', '=', 'branch_staff.user_id')
    ->where('branch_staff.branch_id', $branchId)
    ->where('users.is_active', true)
    ->select('users.id', 'users.name', 'users.email')
    ->get();

echo "   Active staff: " . $staff->count() . "\n";

if ($staff->count() > 0) {
    echo "   Staff members:\n";
    foreach ($staff as $member) {
        echo "   - {$member->name} ({$member->email})\n";
    }
} else {
    echo "   ⚠️  No active staff found!\n";
}

echo "\n";

// ============================================
// 7. SUMMARY
// ============================================
echo "=== SUMMARY ===\n\n";

$issues = [];
$ok = [];

if ($foundPublished === true) {
    $ok[] = "✅ Agent V99 is published";
} elseif ($foundPublished === false) {
    $issues[] = "❌ Agent V99 exists but NOT published";
} else {
    $issues[] = "❌ Agent V99 not found";
}

if ($retellAgentId === $agentId) {
    $ok[] = "✅ Correct agent assigned to branch";
} else {
    $issues[] = "⚠️  Wrong or no agent assigned to branch";
}

if ($foundPhone && isset($phone['agent_id']) && $phone['agent_id'] === $agentId) {
    $ok[] = "✅ Test phone number correctly configured";
} else {
    $issues[] = "⚠️  Phone number not configured or wrong agent";
}

if ($services->count() > 0) {
    $ok[] = "✅ Branch has active services ({$services->count()})";
} else {
    $issues[] = "❌ No active services configured";
}

if ($staff->count() > 0) {
    $ok[] = "✅ Branch has active staff ({$staff->count()})";
} else {
    $issues[] = "❌ No active staff configured";
}

echo "CONFIGURATION OK:\n";
foreach ($ok as $item) {
    echo "  {$item}\n";
}

if (count($issues) > 0) {
    echo "\nISSUES FOUND:\n";
    foreach ($issues as $item) {
        echo "  {$item}\n";
    }
}

echo "\n=== END VERIFICATION ===\n";
