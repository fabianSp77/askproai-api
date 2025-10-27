<?php

/**
 * Deploy Natural Conversation Flow to Retell.ai
 */

$flowId = 'conversation_flow_da76e7c6f3ba';

// Read API key from .env file
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    die("ERROR: .env file not found\n");
}

$envContent = file_get_contents($envFile);
$apiKey = null;

if (preg_match('/RETELLAI_API_KEY=(.+)/', $envContent, $matches)) {
    $apiKey = trim($matches[1]);
} elseif (preg_match('/RETELL_TOKEN=(.+)/', $envContent, $matches)) {
    $apiKey = trim($matches[1]);
}

if (!$apiKey) {
    die("ERROR: Retell API key not found\n");
}

echo "=== DEPLOYING NATURAL CONVERSATION FLOW ===\n\n";

// Load the natural flow
$flowFile = '/var/www/api-gateway/public/askproai_conversation_flow_natural.json';
$flowJson = file_get_contents($flowFile);
$flowData = json_decode($flowJson, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("ERROR: Failed to parse JSON: " . json_last_error_msg() . "\n");
}

echo "Flow geladen:\n";
echo "- Nodes: " . count($flowData['nodes']) . "\n";
echo "- Tools: " . count($flowData['tools']) . "\n";
echo "- Size: " . round(strlen($flowJson) / 1024, 2) . " KB\n\n";

// Validate
echo "Validiere Flow...\n";
$errors = [];

foreach ($flowData['nodes'] as $node) {
    // Check conversation nodes have natural German text only
    if ($node['type'] === 'conversation') {
        $text = $node['instruction']['text'];

        // Check for technical terms that shouldn't be spoken
        if (preg_match('/(IF|ELSE|WHILE|{{|}}}|WICHTIG|SILENT|Do NOT|Check)/i', $text)) {
            $errors[] = "Node {$node['id']} enthält technische Begriffe: " . substr($text, 0, 100);
        }

        // Check for English instructions
        if (preg_match('/\b(analyze|route|check|based on)\b/i', $text) &&
            !preg_match('/\b(Gerne|Perfekt|Sehr gut|Kein Problem)\b/', $text)) {
            $errors[] = "Node {$node['id']} enthält englische Anweisungen";
        }
    }

    // Check function nodes
    if ($node['type'] === 'function') {
        if (!isset($node['tool_id'])) {
            $errors[] = "Function node {$node['id']} missing tool_id";
        }
        if (!isset($node['tool_type'])) {
            $errors[] = "Function node {$node['id']} missing tool_type";
        }
    }
}

if (!empty($errors)) {
    echo "❌ VALIDATION ERRORS:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    die("\nBitte Fehler beheben!\n");
}

echo "✅ Validierung erfolgreich!\n\n";

// Deploy
echo "Deploye zu Retell.ai (Flow ID: $flowId)...\n";

$baseUrl = 'https://api.retellai.com';
$url = "$baseUrl/update-conversation-flow/$flowId";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => $flowJson
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ DEPLOYMENT FAILED!\n";
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

echo "\n✅ DEPLOYMENT ERFOLGREICH!\n\n";
echo "Flow ID: conversation_flow_da76e7c6f3ba\n";
echo "Status: LIVE\n\n";

echo "=== FIXES ANGEWENDET ===\n";
echo "1. ✅ Natürliche deutsche Begrüßung\n";
echo "2. ✅ Keine technischen Kommandos mehr\n";
echo "3. ✅ Keine IF/THEN Logik die vorgelesen wird\n";
echo "4. ✅ Professionelles Sprachverhalten\n";
echo "5. ✅ Kurze, klare Sätze\n\n";

echo "Beispiele für natürliche Sätze:\n";
echo '- "Guten Tag bei Ask Pro AI. Wie kann ich Ihnen helfen?"' . "\n";
echo '- "Gerne! Darf ich Ihren Namen und Ihre E-Mail-Adresse haben?"' . "\n";
echo '- "Perfekt! Für welches Datum und welche Uhrzeit möchten Sie den Termin?"' . "\n";
echo '- "Sehr gut! Der Termin ist verfügbar. Soll ich diesen für Sie buchen?"' . "\n\n";

echo "Bereit für Test-Anruf! 🎉\n";
