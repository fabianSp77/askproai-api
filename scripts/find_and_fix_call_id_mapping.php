<?php

/**
 * Find and Fix call_id Default Value "call_1"
 *
 * The agent is sending "call_1" instead of the actual call ID.
 * This script searches for where "call_1" is defined and removes it.
 */

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🔍 Analysiere Conversation Flow V13...\n\n";

// Get the current flow
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);
$flow = json_decode($response, true);

echo "📋 Flow Version: {$flow['version']}\n";
echo "📋 Is Published: " . ($flow['is_published'] ? 'YES' : 'NO') . "\n\n";

// 1. Check for default_dynamic_variables
echo "🔍 Prüfe default_dynamic_variables...\n";
if (isset($flow['default_dynamic_variables']) && !empty($flow['default_dynamic_variables'])) {
    echo "📋 Gefunden:\n";
    foreach ($flow['default_dynamic_variables'] as $key => $value) {
        $warn = ($key === 'call_id') ? ' ❌ DAS IST DAS PROBLEM!' : '';
        echo "   {$key}: " . json_encode($value) . "{$warn}\n";
    }
    echo "\n";

    if (isset($flow['default_dynamic_variables']['call_id'])) {
        echo "❌ PROBLEM GEFUNDEN: call_id Default-Wert = " . json_encode($flow['default_dynamic_variables']['call_id']) . "\n";
        echo "   Dieser Wert überschreibt die System-Variable {{call_id}}!\n\n";

        $needsFix = true;
    }
} else {
    echo "✅ Keine default_dynamic_variables vorhanden\n\n";
}

// 2. Check parameter mappings in function nodes
echo "🔍 Prüfe Function Node Parameter Mappings...\n\n";
$mappingIssues = [];

foreach ($flow['nodes'] as $index => $node) {
    if ($node['type'] === 'function' && isset($node['parameter_mapping'])) {
        echo "📦 {$node['name']} (ID: {$node['id']})\n";

        if (isset($node['parameter_mapping']['call_id'])) {
            $mapping = $node['parameter_mapping']['call_id'];
            echo "   call_id mapping: {$mapping}";

            if ($mapping === '{{call_id}}') {
                echo " ✅ KORREKT\n";
            } else {
                echo " ❌ FALSCH!\n";
                $mappingIssues[] = [
                    'node_index' => $index,
                    'node_id' => $node['id'],
                    'node_name' => $node['name'],
                    'current_mapping' => $mapping
                ];
            }
        } else {
            echo "   ⚠️  call_id mapping FEHLT!\n";
        }
        echo "\n";
    }
}

// 3. Determine fix strategy
if (!empty($mappingIssues)) {
    echo "\n❌ PARAMETER MAPPING PROBLEME GEFUNDEN:\n\n";
    foreach ($mappingIssues as $issue) {
        echo "   Node: {$issue['node_name']}\n";
        echo "   Current: {$issue['current_mapping']}\n";
        echo "   Should be: {{call_id}}\n\n";
    }

    echo "⚠️  ACHTUNG: Diese Mappings müssen manuell im Retell Dashboard geändert werden!\n";
    echo "   Das API erlaubt keine Node-Änderungen via PATCH.\n\n";
}

// 4. If default_dynamic_variables has call_id, remove it
if (isset($needsFix) && $needsFix) {
    echo "🔧 Entferne call_id aus default_dynamic_variables...\n\n";

    // Remove call_id from defaults
    unset($flow['default_dynamic_variables']['call_id']);

    // If defaults is now empty, remove it entirely
    if (empty($flow['default_dynamic_variables'])) {
        $updatePayload = [
            'default_dynamic_variables' => new stdClass() // Empty object
        ];
    } else {
        $updatePayload = [
            'default_dynamic_variables' => $flow['default_dynamic_variables']
        ];
    }

    $ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updatePayload));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 || $httpCode === 201) {
        $result = json_decode($response, true);
        echo "✅ SUCCESS! Default-Wert entfernt.\n";
        echo "   Neue Version: {$result['version']}\n\n";

        echo "🎯 Nächste Schritte:\n";
        echo "1. Agent muss diese neue Version verwenden\n";
        echo "2. Agent muss published werden\n";
        echo "3. Neuer Test-Call durchführen\n";
    } else {
        echo "❌ Update fehlgeschlagen: HTTP {$httpCode}\n";
        echo "Response: {$response}\n";
    }
} else {
    echo "\n✅ default_dynamic_variables ist OK (kein call_id Default)\n\n";

    if (!empty($mappingIssues)) {
        echo "⚠️  ABER: Node Parameter Mappings sind falsch!\n";
        echo "   Diese müssen im Retell Dashboard manuell korrigiert werden.\n\n";

        echo "📋 Anleitung:\n";
        echo "1. Gehen Sie zu: https://dashboard.retellai.com/conversation-flows/{$flowId}\n";
        echo "2. Für jeden Function Node mit call_id Problem:\n";
        echo "   - Klicken Sie auf den Node\n";
        echo "   - Finden Sie das 'call_id' Parameter Mapping\n";
        echo "   - Ändern Sie es zu: {{call_id}}\n";
        echo "   - Speichern Sie\n";
        echo "3. Publishen Sie die neue Version\n";
    } else {
        echo "✅ ALLES OK! Keine Probleme gefunden.\n";
        echo "   Falls Test-Calls noch fehlschlagen, liegt das Problem woanders.\n";
    }
}
