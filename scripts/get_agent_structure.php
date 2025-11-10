<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$agentId = 'agent_45daa54928c5768b52ba3db736';

echo "Fetching agent structure...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($httpCode === 200) {
    $agent = json_decode($response, true);
    echo "\n=== Agent Top-Level Fields ===\n";
    echo implode("\n", array_keys($agent)) . "\n";

    echo "\n=== Sample user_dtmf_options value ===\n";
    var_dump($agent['user_dtmf_options'] ?? 'NOT SET');

    // Save for reference
    file_put_contents(__DIR__ . '/../current_agent_structure.json', json_encode($agent, JSON_PRETTY_PRINT));
    echo "\n✅ Full agent saved to: current_agent_structure.json\n";
} else {
    echo "Error: $response\n";
}
