<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'] ?? null;
$flowId = 'conversation_flow_1607b81c8f93';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/get-conversation-flow/$flowId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
curl_close($ch);

$flow = json_decode($response, true);

// Find node_03c and show its structure
foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'node_03c_anonymous_customer') {
        echo "═══ node_03c_anonymous_customer Structure ═══\n\n";
        echo json_encode($node, JSON_PRETTY_PRINT);
        echo "\n\n";

        if (isset($node['edges']) && count($node['edges']) > 0) {
            echo "═══ Existing Edges Format ═══\n\n";
            foreach ($node['edges'] as $edge) {
                echo json_encode($edge, JSON_PRETTY_PRINT) . "\n\n";
            }
        }
        break;
    }
}

// Show a function node as example
echo "═══ Example Function Node ═══\n\n";
foreach ($flow['nodes'] as $node) {
    if ($node['type'] === 'function') {
        echo json_encode($node, JSON_PRETTY_PRINT);
        echo "\n\n";
        break;
    }
}
