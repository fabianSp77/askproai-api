<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\Retell\RetellAgentManagementService;

echo "=== ALLE RETELL AGENTS ===\n\n";

$service = new RetellAgentManagementService();

try {
    $agents = $service->listAgents();

    if (is_array($agents) && count($agents) > 0) {
        $friseurAgent = null;

        foreach ($agents as $agent) {
            $name = $agent['agent_name'] ?? 'Unknown';
            $id = $agent['agent_id'] ?? 'Unknown';

            echo "Agent: $name\n";
            echo "ID: $id\n";
            echo "---\n";

            if (stripos($name, 'Friseur') !== false ||
                stripos($name, 'Frisör') !== false ||
                stripos($name, 'Hair') !== false) {
                $friseurAgent = $agent;
                echo "👆 FRISEUR AGENT GEFUNDEN!\n";
            }

            echo "\n";
        }

        echo "\n=== TOTAL: " . count($agents) . " Agents ===\n";

        if ($friseurAgent) {
            echo "\n=== FRISEUR AGENT DETAILS ===\n";
            echo "Name: " . ($friseurAgent['agent_name'] ?? 'Unknown') . "\n";
            echo "ID: " . ($friseurAgent['agent_id'] ?? 'Unknown') . "\n";
        }

    } else {
        echo "⚠️ Keine Agents gefunden oder Fehler\n";
        print_r($agents);
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
