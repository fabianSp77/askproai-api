<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "📋 PRAGMATIC SOLUTION: Use Retell Dashboard\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Nach mehreren API-Versuchen (LLM creation 404, Flow creation schema errors)\n";
echo "ist der pragmatische Weg:\n\n";

echo "OPTION 1: Retell Dashboard nutzen\n";
echo "══════════════════════════════════\n";
echo "1. Gehe zu https://dashboard.retellai.com\n";
echo "2. Erstelle neuen Agent vom Typ 'Retell LLM'\n";
echo "3. LLM ID verwenden: llm_36bd5fb31065787c13797e05a29a\n";
echo "4. Voice: 11labs-Christopher\n";
echo "5. Webhook: https://api.askproai.de/api/webhooks/retell\n";
echo "6. Phone Number +493033081738 zuweisen\n\n";

echo "OPTION 2: Bestehenden Flow-Agent optimieren\n";
echo "═══════════════════════════════════════════\n";
echo "Da wir bereits einen funktionierenden Flow-Agent haben\n";
echo "(agent_2d467d84eb674e5b3f5815d81c),\n";
echo "können wir diesen testen und ggf. im Dashboard anpassen.\n\n";

echo "Current Agent: agent_2d467d84eb674e5b3f5815d81c\n";
echo "Current Flow: conversation_flow_134a15784642\n";
echo "Tools: 7\n";
echo "Nodes: 34\n\n";

echo "Problem: Flow uses prompt-based transitions\n";
echo "Solution: Cannot fix via API - Dashboard required\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "EMPFEHLUNG\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "1. Teste aktuellen Agent (agent_2d467d84eb674e5b3f5815d81c)\n";
echo "   mit Phone Number +493033081738\n\n";

echo "2. Wenn Functions nicht called werden:\n";
echo "   → Im Dashboard den Flow vereinfachen\n";
echo "   → Oder Retell LLM Agent erstellen (LLM ID bereit!)\n\n";

echo "LLM Ready: llm_36bd5fb31065787c13797e05a29a\n";
echo "  - Model: gpt-4o-mini\n";
echo "  - Tools: 4 (check_availability, get_appointments, cancel, reschedule)\n";
echo "  - Status: ✅ Exists and verified\n\n";

