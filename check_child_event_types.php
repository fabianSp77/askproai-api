<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Services\CalcomV2Client;

$company = Company::first();
$client = new CalcomV2Client($company);

// Check for event types assigned to user 1346408 (fabhandy@googlemail.com)
echo "Checking Event Types for User 1346408 (fabhandy@googlemail.com)...\n";
echo str_repeat("=", 80) . "\n\n";

// Get all team event types
$response = $client->getEventTypes();

if ($response->successful()) {
    $eventTypes = $response->json('data');

    // Filter for Ansatzfärbung types
    $ansatzfarbungTypes = array_filter($eventTypes, function($et) {
        return strpos($et['title'] ?? '', 'Ansatzfärbung') !== false;
    });

    echo "All Ansatzfärbung Event Types:\n";
    echo str_repeat("-", 80) . "\n";

    foreach ($ansatzfarbungTypes as $et) {
        echo "ID: {$et['id']}\n";
        echo "Title: {$et['title']}\n";

        // Check if it's a managed event type
        $managedConfig = $et['metadata']['managedEventConfig'] ?? null;
        if ($managedConfig) {
            echo "Type: MANAGED (Child Event Type)\n";
            echo "Parent ID: " . ($managedConfig['parentEventTypeId'] ?? 'N/A') . "\n";
        } else {
            echo "Type: PARENT (can have children)\n";
            // Check hosts
            if (isset($et['hosts'])) {
                echo "Hosts: " . implode(', ', array_map(fn($h) => $h['name'] . ' (ID: ' . $h['userId'] . ')', $et['hosts'])) . "\n";
            }
        }
        echo "\n";
    }

    echo "\nTotal Team Event Types: " . count($eventTypes) . "\n";
    echo "Ansatzfärbung Event Types: " . count($ansatzfarbungTypes) . "\n";
    echo "\n\n";

    // Now specifically check for child event types of 3982562
    echo "Looking for child Event Types of Parent 3982562...\n";
    echo str_repeat("-", 80) . "\n";

    $children = array_filter($eventTypes, function($et) {
        $managedConfig = $et['metadata']['managedEventConfig'] ?? null;
        return $managedConfig && ($managedConfig['parentEventTypeId'] ?? null) == 3982562;
    });

    if (count($children) > 0) {
        foreach ($children as $child) {
            echo "Child ID: {$child['id']}\n";
            echo "Title: {$child['title']}\n";
            if (isset($child['hosts'])) {
                echo "Host: " . ($child['hosts'][0]['name'] ?? 'N/A') . " (ID: " . ($child['hosts'][0]['userId'] ?? 'N/A') . ")\n";
            }
            echo "\n";
        }
    } else {
        echo "⚠️  No child Event Types found for parent 3982562\n";
        echo "This is the issue! Cal.com requires child Event Type IDs for MANAGED types.\n";
    }
} else {
    echo "Error: " . $response->status() . "\n";
    echo $response->body();
}
