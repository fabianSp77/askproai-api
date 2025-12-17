<?php

/**
 * Add second Fabian account to existing MANAGED Event Types
 *
 * This script updates Event Types to include BOTH Fabian accounts as hosts,
 * which will automatically create child Event Types for each.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Services\CalcomV2Client;

// ANSI colors
define('GREEN', "\033[0;32m");
define('YELLOW', "\033[1;33m");
define('RED', "\033[0;31m");
define('BLUE', "\033[0;34m");
define('CYAN', "\033[0;36m");
define('RESET', "\033[0m");
define('BOLD', "\033[1m");

echo BOLD . CYAN . "╔══════════════════════════════════════════════════════════════╗\n" . RESET;
echo BOLD . CYAN . "║  Add Second Fabian to Event Types                           ║\n" . RESET;
echo BOLD . CYAN . "╚══════════════════════════════════════════════════════════════╝\n" . RESET;
echo "\n";

$company = Company::first();
$client = new CalcomV2Client($company);

// Event Types to update (all created for Ansatzfärbung, Ansatz+Längenausgleich, Blondierung)
$eventTypeIds = [
    // Ansatzfärbung
    3982562, 3982564, 3982566, 3982568,
    // Ansatz + Längenausgleich
    3982570, 3982572, 3982574, 3982576,
    // Komplette Umfärbung
    3982578, 3982580, 3982582, 3982584,
];

echo "This will update 12 Event Types to include both Fabian accounts:\n";
echo "  - User 1414768: fabianspitzer@icloud.com (already assigned)\n";
echo "  - User 1346408: fabhandy@googlemail.com (will be added)\n";
echo "\n";
echo YELLOW . "Continue? (yes/no): " . RESET;

$confirm = trim(fgets(STDIN));
if (strtolower($confirm) !== 'yes') {
    echo RED . "Aborted.\n" . RESET;
    exit(0);
}

echo "\n";
echo str_repeat("═", 80) . "\n\n";

$updated = 0;
$errors = [];

foreach ($eventTypeIds as $index => $eventTypeId) {
    $num = $index + 1;
    echo "[$num/12] Updating Event Type $eventTypeId... ";

    try {
        // Update Event Type to include both Fabian accounts
        $response = $client->updateEventType($eventTypeId, [
            'hosts' => [
                [
                    'userId' => 1414768,
                    'mandatory' => true,
                    'priority' => 'high'
                ],
                [
                    'userId' => 1346408,
                    'mandatory' => true,
                    'priority' => 'high'
                ]
            ]
        ]);

        if ($response->successful()) {
            echo GREEN . "✓ Updated\n" . RESET;
            $updated++;
        } else {
            $error = $response->json('message') ?? $response->body();
            echo RED . "✗ Error: $error\n" . RESET;
            $errors[] = ['id' => $eventTypeId, 'error' => $error];
        }

    } catch (\Exception $e) {
        echo RED . "✗ Exception: " . $e->getMessage() . "\n" . RESET;
        $errors[] = ['id' => $eventTypeId, 'error' => $e->getMessage()];
    }

    if ($num < 12) {
        echo "  Waiting 1s...\n";
        sleep(1);
    }
}

echo "\n";
echo str_repeat("═", 80) . "\n";
echo BOLD . GREEN . "\n✓ Update complete!\n" . RESET;
echo "\n";
echo "Summary:\n";
echo "- Updated: " . GREEN . $updated . RESET . " Event Types\n";
echo "- Errors: " . RED . count($errors) . RESET . "\n";

if (!empty($errors)) {
    echo "\n";
    echo RED . "Errors:\n" . RESET;
    foreach ($errors as $error) {
        echo "  - Event Type {$error['id']}: {$error['error']}\n";
    }
}

echo "\n";
echo BOLD . CYAN . "Next: Wait 30s for Cal.com to propagate, then create CalcomEventMaps\n" . RESET;
echo "\n";
