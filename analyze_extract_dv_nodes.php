<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);
$flowId = $agent['response_engine']['conversation_flow_id'] ?? null;

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$flow = json_decode($flowResponse, true);

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║   ANALYSE: Extract DV Nodes im aktuellen Flow              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "Flow Version: " . ($flow['version'] ?? 'N/A') . "\n";
echo "Total Nodes: " . count($flow['nodes']) . "\n\n";

$extractNodes = [];
$expressionTransitions = [];

foreach ($flow['nodes'] as $node) {
    $type = $node['type'] ?? 'N/A';

    // Check for Extract DV nodes
    if (strpos($type, 'extract') !== false || strpos($type, 'dynamic') !== false) {
        $extractNodes[] = [
            'id' => $node['id'] ?? 'N/A',
            'type' => $type,
            'name' => $node['name'] ?? 'N/A',
            'full_structure' => $node
        ];
    }

    // Check for expression-based transitions
    foreach ($node['edges'] ?? [] as $edge) {
        $condType = $edge['transition_condition']['type'] ?? 'N/A';
        if ($condType === 'equation' || $condType === 'expression') {
            $expressionTransitions[] = [
                'from_node' => $node['id'] ?? 'N/A',
                'edge_id' => $edge['id'] ?? 'N/A',
                'condition' => $edge['transition_condition']
            ];
        }
    }
}

echo "=== EXTRACT DYNAMIC VARIABLE NODES ===\n\n";

if (count($extractNodes) > 0) {
    echo "✅ Gefunden: " . count($extractNodes) . " Extract DV Node(s)\n\n";

    foreach ($extractNodes as $i => $extractNode) {
        echo "Extract Node #" . ($i + 1) . ":\n";
        echo "  ID: " . $extractNode['id'] . "\n";
        echo "  Type: " . $extractNode['type'] . "\n";
        echo "  Name: " . $extractNode['name'] . "\n\n";

        echo "  KOMPLETTE STRUKTUR (zum Lernen):\n";
        echo "  " . str_repeat('─', 60) . "\n";
        echo json_encode($extractNode['full_structure'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        echo "  " . str_repeat('─', 60) . "\n\n";
    }
} else {
    echo "❌ KEINE Extract DV Nodes gefunden\n";
    echo "   → Müssen neu erstellt werden\n\n";
}

echo "=== EXPRESSION-BASED TRANSITIONS ===\n\n";

if (count($expressionTransitions) > 0) {
    echo "✅ Gefunden: " . count($expressionTransitions) . " Expression Transition(s)\n\n";

    foreach ($expressionTransitions as $i => $trans) {
        echo "Transition #" . ($i + 1) . ":\n";
        echo "  From Node: " . $trans['from_node'] . "\n";
        echo "  Edge ID: " . $trans['edge_id'] . "\n";
        echo "  Condition Structure:\n";
        echo json_encode($trans['condition'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    }
} else {
    echo "❌ KEINE Expression Transitions gefunden\n";
    echo "   → Alle Transitions sind prompt-based\n\n";
}

// Save complete flow for reference
file_put_contents('/var/www/api-gateway/current_flow_analysis.json', json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "✅ Kompletter Flow gespeichert: current_flow_analysis.json\n";
