<?php
/**
 * Monitor logs for E2E flow debugging
 * Watches for start_booking service lookup logs
 */

$logFile = __DIR__ . '/../storage/logs/laravel.log';

echo "\n=== Monitoring Logs for start_booking Service Lookup ===\n";
echo "Log file: $logFile\n\n";

// Read the last 1000 lines
$output = shell_exec("tail -1000 $logFile");
$lines = explode("\n", $output);

$inStartBooking = false;
$currentEntry = [];

foreach ($lines as $line) {
    // Look for our debug markers
    if (strpos($line, 'start_booking') !== false) {
        echo "Found start_booking reference:\n";
        echo "  $line\n";
    }

    if (strpos($line, 'STEP 4') !== false) {
        echo "\nFound STEP 4 log:\n";
        echo "  $line\n";
    }

    if (strpos($line, 'Service lookup') !== false) {
        echo "\nFound Service lookup log:\n";
        echo "  $line\n";
    }

    if (strpos($line, 'Service found') !== false) {
        echo "\nFound Service found log:\n";
        echo "  $line\n";
    }

    if (strpos($line, 'No service found') !== false) {
        echo "\nFound No service found log:\n";
        echo "  $line\n";
    }

    if (strpos($line, '❌ start_booking') !== false) {
        echo "\nFound error in start_booking:\n";
        echo "  $line\n";
    }

    if (strpos($line, '✅ start_booking') !== false) {
        echo "\nFound success in start_booking:\n";
        echo "  $line\n";
    }
}

echo "\n=== Searching for recent datetime parsing issues ===\n";

// Look for datetime-related errors
$dtErrors = shell_exec("tail -2000 $logFile | grep -i 'datetime\\|parse.*date' | tail -20");
if ($dtErrors) {
    echo $dtErrors;
} else {
    echo "No datetime parsing issues found\n";
}

echo "\n=== Checking service lookup attempts ===\n";

// Look for service selection logs
$serviceSelectors = shell_exec("tail -2000 $logFile | grep -E 'findServiceByName|findServiceById|getDefaultService' | tail -20");
if ($serviceSelectors) {
    echo $serviceSelectors;
} else {
    echo "No service selection logs found\n";
}

echo "\n=== End of Log Analysis ===\n\n";
