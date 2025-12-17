<?php

/**
 * Interactive CalcomEventMap Setup Script
 *
 * This script helps create CalcomEventMaps for missing service/staff combinations
 * It guides you through creating Cal.com Event Types and then creates the DB entries
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Service;
use App\Models\Staff;
use App\Models\CalcomEventMap;
use App\Services\CalcomV2Client;
use App\Services\CalcomChildEventTypeResolver;

// ANSI color codes
define('GREEN', "\033[0;32m");
define('YELLOW', "\033[1;33m");
define('RED', "\033[0;31m");
define('BLUE', "\033[0;34m");
define('CYAN', "\033[0;36m");
define('RESET', "\033[0m");
define('BOLD', "\033[1m");

echo BOLD . CYAN . "╔══════════════════════════════════════════════════════════════╗\n" . RESET;
echo BOLD . CYAN . "║     CalcomEventMap Interactive Setup Wizard                  ║\n" . RESET;
echo BOLD . CYAN . "╚══════════════════════════════════════════════════════════════╝\n" . RESET;
echo "\n";

// Configuration
$missingCombinations = [
    [
        'service_id' => 440,
        'service_name' => 'Ansatzfärbung',
        'staff_id' => '6ad1fa25-12cf-4939-8fb9-c5f5cf407dfe',
        'staff_name' => 'Fabian Spitzer (fabianspitzer@icloud.com)',
        'calcom_user_id' => 1414768,
    ],
    [
        'service_id' => 440,
        'service_name' => 'Ansatzfärbung',
        'staff_id' => '9f47fda1-977c-47aa-a87a-0e8cbeaeb119',
        'staff_name' => 'Fabian Spitzer (fabhandy@googlemail.com)',
        'calcom_user_id' => 1346408,
    ],
    [
        'service_id' => 442,
        'service_name' => 'Ansatz + Längenausgleich',
        'staff_id' => '6ad1fa25-12cf-4939-8fb9-c5f5cf407dfe',
        'staff_name' => 'Fabian Spitzer (fabianspitzer@icloud.com)',
        'calcom_user_id' => 1414768,
    ],
    [
        'service_id' => 442,
        'service_name' => 'Ansatz + Längenausgleich',
        'staff_id' => '9f47fda1-977c-47aa-a87a-0e8cbeaeb119',
        'staff_name' => 'Fabian Spitzer (fabhandy@googlemail.com)',
        'calcom_user_id' => 1346408,
    ],
    [
        'service_id' => 444,
        'service_name' => 'Komplette Umfärbung (Blondierung)',
        'staff_id' => '6ad1fa25-12cf-4939-8fb9-c5f5cf407dfe',
        'staff_name' => 'Fabian Spitzer (fabianspitzer@icloud.com)',
        'calcom_user_id' => 1414768,
    ],
    [
        'service_id' => 444,
        'service_name' => 'Komplette Umfärbung (Blondierung)',
        'staff_id' => '9f47fda1-977c-47aa-a87a-0e8cbeaeb119',
        'staff_name' => 'Fabian Spitzer (fabhandy@googlemail.com)',
        'calcom_user_id' => 1346408,
    ],
];

// Menu
echo BOLD . "What would you like to do?\n" . RESET;
echo "1. " . GREEN . "Show missing combinations overview\n" . RESET;
echo "2. " . YELLOW . "Generate Cal.com Event Type creation guide\n" . RESET;
echo "3. " . BLUE . "Create CalcomEventMaps (after Event Types are created in Cal.com)\n" . RESET;
echo "4. " . CYAN . "Verify existing CalcomEventMaps\n" . RESET;
echo "5. " . RED . "Exit\n" . RESET;
echo "\n";
echo "Enter choice (1-5): ";

$choice = trim(fgets(STDIN));

switch ($choice) {
    case '1':
        showMissingCombinations($missingCombinations);
        break;
    case '2':
        generateEventTypeGuide($missingCombinations);
        break;
    case '3':
        createCalcomEventMaps($missingCombinations);
        break;
    case '4':
        verifyCalcomEventMaps();
        break;
    case '5':
        echo GREEN . "Goodbye!\n" . RESET;
        exit(0);
    default:
        echo RED . "Invalid choice!\n" . RESET;
        exit(1);
}

// Functions

function showMissingCombinations($combinations) {
    echo BOLD . CYAN . "\n╔══════════════════════════════════════════════════════════════╗\n" . RESET;
    echo BOLD . CYAN . "║  Missing CalcomEventMap Combinations                         ║\n" . RESET;
    echo BOLD . CYAN . "╚══════════════════════════════════════════════════════════════╝\n" . RESET;
    echo "\n";

    $grouped = [];
    foreach ($combinations as $combo) {
        $key = $combo['service_name'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [];
        }
        $grouped[$key][] = $combo;
    }

    foreach ($grouped as $serviceName => $combos) {
        echo BOLD . BLUE . "Service: " . $serviceName . "\n" . RESET;
        echo str_repeat("═", 60) . "\n";

        $service = Service::find($combos[0]['service_id']);
        $segments = $service->segments;

        $activeSegments = array_filter($segments, function($seg) {
            return $seg['staff_required'] ?? true;
        });

        echo "Segments: " . count($activeSegments) . " active phases\n";
        foreach ($activeSegments as $seg) {
            echo "  • " . $seg['key'] . ": " . $seg['name'] . "\n";
        }

        echo "\nMissing for:\n";
        foreach ($combos as $combo) {
            echo "  → " . $combo['staff_name'] . "\n";
            echo "    Cal.com User ID: " . $combo['calcom_user_id'] . "\n";
            echo "    Event Types needed: " . count($activeSegments) . "\n";
        }
        echo "\n";
    }

    $totalEventTypes = 0;
    foreach ($combinations as $combo) {
        $service = Service::find($combo['service_id']);
        $activeSegments = array_filter($service->segments, function($seg) {
            return $seg['staff_required'] ?? true;
        });
        $totalEventTypes += count($activeSegments);
    }

    echo BOLD . GREEN . "Total Event Types to create: " . $totalEventTypes . "\n" . RESET;
    echo BOLD . GREEN . "Total CalcomEventMap entries: " . $totalEventTypes . "\n" . RESET;
}

function generateEventTypeGuide($combinations) {
    echo BOLD . CYAN . "\n╔══════════════════════════════════════════════════════════════╗\n" . RESET;
    echo BOLD . CYAN . "║  Cal.com Event Type Creation Guide                          ║\n" . RESET;
    echo BOLD . CYAN . "╚══════════════════════════════════════════════════════════════╝\n" . RESET;
    echo "\n";

    echo YELLOW . "Instructions:\n" . RESET;
    echo "1. Login to Cal.com as Admin\n";
    echo "2. Navigate to Event Types > Create Event Type\n";
    echo "3. For EACH combination below, create the listed Event Types\n";
    echo "4. Note down the Event Type IDs (from URL when editing)\n";
    echo "5. Run this script again with option 3 to create CalcomEventMaps\n";
    echo "\n";

    echo str_repeat("═", 80) . "\n\n";

    $counter = 1;
    foreach ($combinations as $combo) {
        $service = Service::find($combo['service_id']);
        $segments = $service->segments;

        $activeSegments = array_filter($segments, function($seg) {
            return $seg['staff_required'] ?? true;
        });

        echo BOLD . BLUE . "[$counter] Service: " . $combo['service_name'] . "\n" . RESET;
        echo BOLD . BLUE . "    Staff: " . $combo['staff_name'] . "\n" . RESET;
        echo BOLD . BLUE . "    Cal.com User ID: " . $combo['calcom_user_id'] . "\n" . RESET;
        echo str_repeat("-", 80) . "\n";

        $segmentCounter = 1;
        $totalSegments = count($activeSegments);

        foreach ($activeSegments as $seg) {
            echo "\n";
            echo GREEN . "  Segment " . $seg['key'] . " ($segmentCounter von $totalSegments):\n" . RESET;
            echo "  Name: " . CYAN . $combo['service_name'] . ": " . $seg['name'] . " ($segmentCounter von $totalSegments) - Fabian Spitzer\n" . RESET;
            echo "  Duration: " . ($seg['durationMin'] ?? 'TBD') . " minutes\n";
            echo "  Type: Event Type (assign to user " . $combo['calcom_user_id'] . ")\n";
            echo "  Team: Friseur 1\n";
            echo "\n";
            echo "  " . YELLOW . "→ After creation, note Event Type ID: _________\n" . RESET;
            echo "\n";
            $segmentCounter++;
        }

        echo "\n" . str_repeat("═", 80) . "\n\n";
        $counter++;
    }

    echo BOLD . RED . "⚠️  IMPORTANT: Save all Event Type IDs before proceeding!\n" . RESET;
}

function createCalcomEventMaps($combinations) {
    echo BOLD . CYAN . "\n╔══════════════════════════════════════════════════════════════╗\n" . RESET;
    echo BOLD . CYAN . "║  Create CalcomEventMaps                                      ║\n" . RESET;
    echo BOLD . CYAN . "╚══════════════════════════════════════════════════════════════╝\n" . RESET;
    echo "\n";

    echo YELLOW . "This will create CalcomEventMap entries in the database.\n" . RESET;
    echo YELLOW . "Make sure you have created the Event Types in Cal.com first!\n" . RESET;
    echo "\n";

    echo "Do you want to proceed? (yes/no): ";
    $confirm = trim(fgets(STDIN));

    if (strtolower($confirm) !== 'yes') {
        echo RED . "Aborted.\n" . RESET;
        return;
    }

    echo "\n";
    echo BOLD . "Enter Event Type IDs for each combination:\n" . RESET;
    echo str_repeat("═", 80) . "\n\n";

    $created = 0;

    foreach ($combinations as $combo) {
        $service = Service::find($combo['service_id']);
        $segments = $service->segments;

        $activeSegments = array_filter($segments, function($seg) {
            return $seg['staff_required'] ?? true;
        });

        echo BOLD . BLUE . "Service: " . $combo['service_name'] . "\n" . RESET;
        echo BOLD . BLUE . "Staff: " . $combo['staff_name'] . "\n" . RESET;
        echo str_repeat("-", 80) . "\n";

        foreach ($activeSegments as $seg) {
            echo "\nSegment " . GREEN . $seg['key'] . RESET . ": " . $seg['name'] . "\n";

            // Check if already exists
            $existing = CalcomEventMap::where('service_id', $combo['service_id'])
                ->where('staff_id', $combo['staff_id'])
                ->where('segment_key', $seg['key'])
                ->first();

            if ($existing) {
                echo YELLOW . "  ⚠️  Already exists (Event Type ID: {$existing->event_type_id})\n" . RESET;
                echo "  Skip or Update? (s/u): ";
                $action = trim(fgets(STDIN));

                if ($action === 's') {
                    continue;
                }
            }

            echo "  Enter Event Type ID: ";
            $eventTypeId = trim(fgets(STDIN));

            if (empty($eventTypeId) || !is_numeric($eventTypeId)) {
                echo RED . "  ✗ Invalid ID, skipping...\n" . RESET;
                continue;
            }

            try {
                if ($existing) {
                    $existing->update([
                        'event_type_id' => $eventTypeId,
                        'updated_at' => now(),
                    ]);
                    echo GREEN . "  ✓ Updated CalcomEventMap\n" . RESET;
                } else {
                    CalcomEventMap::create([
                        'service_id' => $combo['service_id'],
                        'segment_key' => $seg['key'],
                        'staff_id' => $combo['staff_id'],
                        'event_type_id' => $eventTypeId,
                        'child_event_type_id' => null, // Will be resolved during sync
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    echo GREEN . "  ✓ Created CalcomEventMap\n" . RESET;
                    $created++;
                }
            } catch (\Exception $e) {
                echo RED . "  ✗ Error: " . $e->getMessage() . "\n" . RESET;
            }
        }

        echo "\n";
    }

    echo str_repeat("═", 80) . "\n";
    echo BOLD . GREEN . "\n✓ Created $created new CalcomEventMaps\n" . RESET;
    echo BOLD . CYAN . "\nNext: Run option 4 to verify the setup\n" . RESET;
}

function verifyCalcomEventMaps() {
    echo BOLD . CYAN . "\n╔══════════════════════════════════════════════════════════════╗\n" . RESET;
    echo BOLD . CYAN . "║  Verify CalcomEventMaps                                      ║\n" . RESET;
    echo BOLD . CYAN . "╚══════════════════════════════════════════════════════════════╝\n" . RESET;
    echo "\n";

    $serviceIds = [440, 442, 444];
    $staffIds = [
        '6ad1fa25-12cf-4939-8fb9-c5f5cf407dfe',
        '9f47fda1-977c-47aa-a87a-0e8cbeaeb119'
    ];

    $totalExpected = 0;
    $totalFound = 0;

    foreach ($serviceIds as $serviceId) {
        $service = Service::find($serviceId);
        $activeSegments = array_filter($service->segments, function($seg) {
            return $seg['staff_required'] ?? true;
        });

        echo BOLD . BLUE . "Service: " . $service->name . " (ID: $serviceId)\n" . RESET;
        echo str_repeat("-", 80) . "\n";

        foreach ($staffIds as $staffId) {
            $staff = Staff::find($staffId);
            echo "\n  Staff: " . CYAN . $staff->name . " (" . $staff->email . ")\n" . RESET;

            $maps = CalcomEventMap::where('service_id', $serviceId)
                ->where('staff_id', $staffId)
                ->orderBy('segment_key')
                ->get();

            $expected = count($activeSegments);
            $found = $maps->count();

            $totalExpected += $expected;
            $totalFound += $found;

            if ($found === $expected) {
                echo "  " . GREEN . "✓ Complete: $found / $expected segments\n" . RESET;
            } else {
                echo "  " . RED . "✗ Incomplete: $found / $expected segments\n" . RESET;
            }

            foreach ($maps as $map) {
                echo "    • Segment " . $map->segment_key . ": Event Type " . $map->event_type_id;
                if ($map->child_event_type_id) {
                    echo " (Child: " . $map->child_event_type_id . ")";
                }
                echo "\n";
            }

            if ($found < $expected) {
                $missingSegments = array_filter($activeSegments, function($seg) use ($maps) {
                    return !$maps->contains('segment_key', $seg['key']);
                });

                echo "  " . YELLOW . "Missing segments:\n" . RESET;
                foreach ($missingSegments as $seg) {
                    echo "    - " . $seg['key'] . ": " . $seg['name'] . "\n";
                }
            }
        }

        echo "\n";
    }

    echo str_repeat("═", 80) . "\n";
    if ($totalFound === $totalExpected) {
        echo BOLD . GREEN . "\n✓ All CalcomEventMaps complete! ($totalFound / $totalExpected)\n" . RESET;
        echo BOLD . CYAN . "\nSystem is ready for production use!\n" . RESET;
    } else {
        echo BOLD . RED . "\n✗ Incomplete setup: $totalFound / $totalExpected\n" . RESET;
        echo BOLD . YELLOW . "\nRun option 2 to see what's missing, then option 3 to complete setup.\n" . RESET;
    }
}
