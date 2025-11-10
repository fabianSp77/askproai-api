<?php
/**
 * List all phone numbers and their agent assignments
 */

echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  📞 PHONE NUMBERS OVERVIEW                                   ║" . PHP_EOL;
echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$currentAgentId = 'agent_45daa54928c5768b52ba3db736';

$ch = curl_init("https://api.retellai.com/list-phone-numbers");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$phoneNumbers = json_decode($response, true);

if (empty($phoneNumbers)) {
    echo "❌ No phone numbers found in Retell account" . PHP_EOL;
    exit(1);
}

echo "Total phone numbers: " . count($phoneNumbers) . PHP_EOL;
echo PHP_EOL;

$assignedToCurrentAgent = [];
$assignedToOtherAgent = [];
$unassigned = [];

foreach ($phoneNumbers as $phone) {
    if (isset($phone['agent_id'])) {
        if ($phone['agent_id'] === $currentAgentId) {
            $assignedToCurrentAgent[] = $phone;
        } else {
            $assignedToOtherAgent[] = $phone;
        }
    } else {
        $unassigned[] = $phone;
    }
}

// Show current agent's numbers
echo "═══ ASSIGNED TO CURRENT AGENT (Friseur 1 V51) ═══" . PHP_EOL;
if (empty($assignedToCurrentAgent)) {
    echo "❌ NO PHONE NUMBERS ASSIGNED" . PHP_EOL;
    echo "   Agent ID: {$currentAgentId}" . PHP_EOL;
} else {
    foreach ($assignedToCurrentAgent as $phone) {
        echo "✅ {$phone['phone_number']}" . PHP_EOL;
        echo "   Nickname: {$phone['nickname']}" . PHP_EOL;
        echo "   ID: {$phone['phone_number_id']}" . PHP_EOL;
    }
}
echo PHP_EOL;

// Show unassigned numbers
echo "═══ UNASSIGNED PHONE NUMBERS ═══" . PHP_EOL;
if (empty($unassigned)) {
    echo "No unassigned phone numbers" . PHP_EOL;
} else {
    foreach ($unassigned as $phone) {
        echo "📞 {$phone['phone_number']}" . PHP_EOL;
        echo "   Nickname: {$phone['nickname']}" . PHP_EOL;
        echo "   ID: {$phone['phone_number_id']}" . PHP_EOL;
        echo "   Available for assignment" . PHP_EOL;
        echo PHP_EOL;
    }
}
echo PHP_EOL;

// Show numbers assigned to other agents
echo "═══ ASSIGNED TO OTHER AGENTS ═══" . PHP_EOL;
if (empty($assignedToOtherAgent)) {
    echo "No phone numbers assigned to other agents" . PHP_EOL;
} else {
    foreach ($assignedToOtherAgent as $phone) {
        echo "📞 {$phone['phone_number']}" . PHP_EOL;
        echo "   Nickname: {$phone['nickname']}" . PHP_EOL;
        echo "   Agent ID: {$phone['agent_id']}" . PHP_EOL;
        echo "   ID: {$phone['phone_number_id']}" . PHP_EOL;
        echo PHP_EOL;
    }
}

echo PHP_EOL;
echo "╔═══════════════════════════════════════════════════════════════╗" . PHP_EOL;
echo "║  📋 SUMMARY                                                  ║" . PHP_EOL;
echo "╚═══════════════════════════════════════════════════════════════╝" . PHP_EOL;
echo PHP_EOL;
echo "Current Agent: " . count($assignedToCurrentAgent) . " phone(s)" . PHP_EOL;
echo "Unassigned: " . count($unassigned) . " phone(s)" . PHP_EOL;
echo "Other Agents: " . count($assignedToOtherAgent) . " phone(s)" . PHP_EOL;
echo PHP_EOL;

if (empty($assignedToCurrentAgent)) {
    echo "⚠️ ACTION REQUIRED:" . PHP_EOL;
    echo "   Agent needs a phone number to receive calls!" . PHP_EOL;
    echo PHP_EOL;
    if (!empty($unassigned)) {
        echo "   You can assign one of the unassigned numbers above." . PHP_EOL;
    } elseif (!empty($assignedToOtherAgent)) {
        echo "   You may need to reassign a number from another agent." . PHP_EOL;
    } else {
        echo "   You may need to purchase/add a phone number in Retell." . PHP_EOL;
    }
}
