#!/usr/bin/env php
<?php
/**
 * Update YOUR Retell Agent with YOUR API Key
 * 
 * INSTRUCTIONS:
 * 1. Go to: https://dashboard.retellai.com/api-keys
 * 2. Copy YOUR API key
 * 3. Replace YOUR_API_KEY_HERE below
 * 4. Run: php update-retell-with-your-key.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\033[1;36m================================================================================\033[0m\n";
echo "\033[1;33m🔑 Retell Agent Update with YOUR API Key\033[0m\n";
echo "\033[1;36m================================================================================\033[0m\n\n";

// ⚠️ REPLACE THIS WITH YOUR ACTUAL API KEY FROM RETELL DASHBOARD
$YOUR_API_KEY = 'YOUR_API_KEY_HERE';  // <-- CHANGE THIS!

if ($YOUR_API_KEY === 'YOUR_API_KEY_HERE') {
    echo "\033[0;31m❌ ERROR: Please replace YOUR_API_KEY_HERE with your actual Retell API key!\033[0m\n\n";
    echo "Instructions:\n";
    echo "1. Go to: https://dashboard.retellai.com/api-keys\n";
    echo "2. Copy your API key\n";
    echo "3. Edit this file and replace YOUR_API_KEY_HERE\n";
    echo "4. Run this script again\n\n";
    exit(1);
}

$AGENT_ID = 'agent_d7da9e5c49c4ccfff2526df5c1';

echo "Using YOUR API Key: " . substr($YOUR_API_KEY, 0, 10) . "...\n";
echo "Updating Agent: $AGENT_ID\n";
echo str_repeat('-', 80) . "\n\n";

// Hair Salon Prompt
$hairSalonPrompt = <<<'PROMPT'
Du bist der freundliche KI-Assistent für einen Friseursalon mit 3 Mitarbeiterinnen: Paula (ID:1), Claudia (ID:2) und Katrin (ID:3).

## Deine Hauptaufgaben:
1. Termine für Friseurdienstleistungen vereinbaren
2. Dienstleistungen und Preise erklären
3. Beratungstermine für spezielle Services vereinbaren

## Mitarbeiterauswahl:
Wenn der Kunde keinen speziellen Mitarbeiter wünscht:
- Prüfe die Verfügbarkeit aller drei Mitarbeiterinnen
- Biete die nächsten verfügbaren Termine an
- Erwähne bei wem der Termin wäre

Wenn der Kunde einen bestimmten Mitarbeiter möchte:
- Nutze nur die staff_id dieser Mitarbeiterin
- Paula = staff_id: 1
- Claudia = staff_id: 2  
- Katrin = staff_id: 3

## Dienstleistungen die Beratung benötigen:
Diese Services erfordern IMMER einen Beratungsrückruf:
- Strähnen (alle Varianten)
- Blondierung
- Balayage
- Faceframe

Für diese sage: "Das ist eine tolle Wahl! Für [Service] ist eine persönliche Beratung wichtig. Ich vereinbare gerne einen Rückruf für Sie."

## Gesprächsablauf:
1. Begrüße freundlich und frage nach dem Wunsch
2. Bei Beratungsservices → schedule_callback nutzen
3. Bei normalen Services → check_availability → book_appointment
4. Bestätige alle Termine mit allen Details
5. Verabschiede dich freundlich

## Wichtige Hinweise:
- Nutze IMMER die MCP Funktionen für alle Aktionen
- Bestätige IMMER Name und Telefonnummer
- Bei Unsicherheiten höflich nachfragen
- Sprich natürlich und freundlich auf Deutsch
PROMPT;

// Prepare update data
$updateData = [
    'general_prompt' => $hairSalonPrompt,
    'agent_name' => 'Hair Salon Assistant - 3 Mitarbeiterinnen',
    'language' => 'de',
    'webhook_url' => 'https://api.askproai.de/api/v2/hair-salon-mcp/retell-webhook'
];

// Make the API call directly
$ch = curl_init("https://api.retellai.com/update-agent/$AGENT_ID");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $YOUR_API_KEY,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));

echo "📤 Sending update request...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    echo "\033[0;32m✅ SUCCESS! Agent updated!\033[0m\n\n";
    
    $agent = json_decode($response, true);
    echo "Updated Agent Details:\n";
    echo "• Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
    echo "• Language: " . ($agent['language'] ?? 'N/A') . "\n";
    echo "• Webhook: " . ($agent['webhook_url'] ?? 'N/A') . "\n";
    
    echo "\n\033[1;32m🎉 Your agent has been successfully updated!\033[0m\n";
    echo "Check it here: https://dashboard.retellai.com/agents/$AGENT_ID\n";
    
} else {
    echo "\033[0;31m❌ Update failed (HTTP $httpCode)\033[0m\n";
    
    if ($httpCode === 401) {
        echo "\nAuthentication failed. This API key doesn't have access to this agent.\n";
        echo "Make sure you're using the API key from the same account that owns the agent.\n";
    } elseif ($httpCode === 404) {
        echo "\nAgent not found. The agent ID might be incorrect.\n";
    } else {
        echo "\nResponse: " . substr($response, 0, 500) . "\n";
    }
}

echo "\n\033[1;36m================================================================================\033[0m\n";
echo "Next steps:\n";
echo "1. Verify the update in your dashboard\n";
echo "2. Add the Custom Functions (see retell-agent-setup.html)\n";
echo "3. Configure @MCP section\n";
echo "4. Test with a call to +493033081738\n";
echo "\033[1;36m================================================================================\033[0m\n";